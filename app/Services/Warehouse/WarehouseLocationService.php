<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseZone;
use App\Models\Warehouse\WarehouseAisle;
use App\Models\Warehouse\WarehouseRack;
use App\Models\Warehouse\WarehouseShelf;
use App\Models\Warehouse\WarehouseBin;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductWarehouse;
use App\Models\Marketplace\InventoryMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Warehouse Location Service
 * 
 * Manages warehouse location hierarchy: Zone → Aisle → Rack → Shelf → Bin
 * Handles put-away, picking, and bin assignment operations.
 */
class WarehouseLocationService
{
    /**
     * Create a complete location hierarchy
     */
    public function createLocationHierarchy(
        int $warehouseId,
        array $zonesData,
        bool $generateCodes = true
    ): array {
        return DB::transaction(function () use ($warehouseId, $zonesData, $generateCodes) {
            $result = ['zones' => [], 'success' => true];

            foreach ($zonesData as $zoneData) {
                $zone = WarehouseZone::create([
                    'warehouse_id' => $warehouseId,
                    'name' => $zoneData['name'],
                    'code' => $generateCodes ? $this->generateZoneCode($warehouseId, $zoneData['name']) : ($zoneData['code'] ?? null),
                    'description' => $zoneData['description'] ?? null,
                    'type' => $zoneData['type'] ?? 'storage',
                    'temperature_min' => $zoneData['temperature_min'] ?? null,
                    'temperature_max' => $zoneData['temperature_max'] ?? null,
                    'is_active' => true,
                ]);

                $zoneResult = ['zone' => $zone, 'aisles' => []];

                if (isset($zoneData['aisles'])) {
                    foreach ($zoneData['aisles'] as $aisleData) {
                        $aisle = WarehouseAisle::create([
                            'zone_id' => $zone->id,
                            'name' => $aisleData['name'],
                            'code' => $generateCodes ? $this->generateAisleCode($zone->id, $aisleData['name']) : ($aisleData['code'] ?? null),
                            'sequence' => $aisleData['sequence'] ?? 0,
                            'description' => $aisleData['description'] ?? null,
                            'is_active' => true,
                        ]);

                        $aisleResult = ['aisle' => $aisle, 'racks' => []];

                        if (isset($aisleData['racks'])) {
                            foreach ($aisleData['racks'] as $rackData) {
                                $rack = WarehouseRack::create([
                                    'aisle_id' => $aisle->id,
                                    'name' => $rackData['name'],
                                    'code' => $generateCodes ? $this->generateRackCode($aisle->id, $rackData['name']) : ($rackData['code'] ?? null),
                                    'sequence' => $rackData['sequence'] ?? 0,
                                    'levels' => $rackData['levels'] ?? 1,
                                    'max_weight_kg' => $rackData['max_weight_kg'] ?? null,
                                    'max_height_cm' => $rackData['max_height_cm'] ?? null,
                                    'description' => $rackData['description'] ?? null,
                                    'is_active' => true,
                                ]);

                                $rackResult = ['rack' => $rack, 'shelves' => []];

                                if (isset($rackData['shelves'])) {
                                    foreach ($rackData['shelves'] as $shelfData) {
                                        $shelf = WarehouseShelf::create([
                                            'rack_id' => $rack->id,
                                            'name' => $shelfData['name'],
                                            'code' => $generateCodes ? $this->generateShelfCode($rack->id, $shelfData['name']) : ($shelfData['code'] ?? null),
                                            'level_number' => $shelfData['level_number'] ?? 1,
                                            'sequence' => $shelfData['sequence'] ?? 0,
                                            'max_weight_kg' => $shelfData['max_weight_kg'] ?? null,
                                            'description' => $shelfData['description'] ?? null,
                                            'is_active' => true,
                                        ]);

                                        $shelfResult = ['shelf' => $shelf, 'bins' => []];

                                        if (isset($shelfData['bins'])) {
                                            foreach ($shelfData['bins'] as $binData) {
                                                $bin = WarehouseBin::create([
                                                    'shelf_id' => $shelf->id,
                                                    'name' => $binData['name'],
                                                    'code' => $generateCodes ? $this->generateBinCode($shelf->id, $binData['name']) : ($binData['code'] ?? null),
                                                    'sequence' => $binData['sequence'] ?? 0,
                                                    'type' => $binData['type'] ?? 'standard',
                                                    'capacity_volume_m3' => $binData['capacity_volume_m3'] ?? null,
                                                    'max_weight_kg' => $binData['max_weight_kg'] ?? null,
                                                    'max_items' => $binData['max_items'] ?? null,
                                                    'is_active' => true,
                                                ]);

                                                $shelfResult['bins'][] = $bin;
                                            }
                                        }

                                        $rackResult['shelves'][] = $shelfResult;
                                    }
                                }

                                $aisleResult['racks'][] = $rackResult;
                            }
                        }

                        $zoneResult['aisles'][] = $aisleResult;
                    }
                }

                $result['zones'][] = $zoneResult;
            }

            return $result;
        });
    }

    /**
     * Find an available bin for a product
     */
    public function findAvailableBin(
        int $productId,
        int $warehouseId,
        ?string $productType = null,
        ?float $volume = null,
        ?float $weight = null
    ): ?WarehouseBin {
        $query = WarehouseBin::join('warehouse_shelves', 'warehouse_bins.shelf_id', '=', 'warehouse_shelves.id')
            ->join('warehouse_racks', 'warehouse_shelves.rack_id', '=', 'warehouse_racks.id')
            ->join('warehouse_aisles', 'warehouse_racks.aisle_id', '=', 'warehouse_aisles.id')
            ->join('warehouse_zones', 'warehouse_aisles.zone_id', '=', 'warehouse_zones.id')
            ->where('warehouse_zones.warehouse_id', $warehouseId)
            ->where('warehouse_bins.is_active', true)
            ->where('warehouse_shelves.is_active', true)
            ->where('warehouse_racks.is_active', true)
            ->where('warehouse_aisles.is_active', true)
            ->where('warehouse_zones.is_active', true);

        // Filter by bin type if product type specified
        if ($productType) {
            $typeMapping = [
                'small' => 'small_parts',
                'pallet' => 'pallet',
                'bulk' => 'bulk',
                'cold' => 'cold',
                'hazmat' => 'hazmat',
            ];

            if (isset($typeMapping[$productType])) {
                $query->where('warehouse_bins.type', $typeMapping[$productType]);
            }
        }

        // Filter by weight capacity
        if ($weight) {
            $query->where(function ($q) use ($weight) {
                $q->whereNull('warehouse_bins.max_weight_kg')
                  ->orWhere('warehouse_bins.max_weight_kg', '>=', $weight);
            });
        }

        // Get bins ordered by sequence
        $bins = $query->select('warehouse_bins.*')
            ->orderBy('warehouse_zones.sequence')
            ->orderBy('warehouse_aisles.sequence')
            ->orderBy('warehouse_racks.sequence')
            ->orderBy('warehouse_shelves.sequence')
            ->orderBy('warehouse_bins.sequence')
            ->get();

        // Return first bin with capacity or any bin if no capacity constraints
        foreach ($bins as $bin) {
            if (!$bin->max_items) {
                return $bin;
            }

            $currentItemCount = ProductWarehouse::where('bin_id', $bin->id)->count();
            if ($currentItemCount < $bin->max_items) {
                return $bin;
            }
        }

        return $bins->first();
    }

    /**
     * Assign a product to a bin
     */
    public function assignProductToBin(
        int $productId,
        int $warehouseId,
        ?int $binId = null,
        ?User $user = null
    ): ProductWarehouse {
        return DB::transaction(function () use ($productId, $warehouseId, $binId, $user) {
            // Find or create product-warehouse record
            $productWarehouse = ProductWarehouse::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['available_stock' => 0, 'reserved_stock' => 0]
            );

            // Update bin assignment
            if ($binId) {
                $productWarehouse->update(['bin_id' => $binId]);
            } elseif (!$productWarehouse->bin_id) {
                // Auto-assign bin if none specified
                $product = Product::find($productId);
                $bin = $this->findAvailableBin($productId, $warehouseId);
                
                if ($bin) {
                    $productWarehouse->update(['bin_id' => $bin->id]);
                }
            }

            // Log the assignment
            if ($user) {
                $this->logActivity(
                    $warehouseId,
                    $user->id,
                    'product_bin_assigned',
                    'Product assigned to bin',
                    ProductWarehouse::class,
                    $productWarehouse->id,
                    ['product_id' => $productId, 'bin_id' => $productWarehouse->bin_id]
                );
            }

            return $productWarehouse->fresh();
        });
    }

    /**
     * Generate location code from hierarchy
     */
    public function getLocationPath(int $binId): string
    {
        $bin = WarehouseBin::with([
            'shelf.rack.aisle.zone.warehouse'
        ])->find($binId);

        if (!$bin) {
            return '';
        }

        return $bin->full_location_path;
    }

    /**
     * Get all bins in a warehouse with stock counts
     */
    public function getWarehouseBinsWithStock(int $warehouseId): array
    {
        $bins = WarehouseBin::join('warehouse_shelves', 'warehouse_bins.shelf_id', '=', 'warehouse_shelves.id')
            ->join('warehouse_racks', 'warehouse_shelves.rack_id', '=', 'warehouse_racks.id')
            ->join('warehouse_aisles', 'warehouse_racks.aisle_id', '=', 'warehouse_aisles.id')
            ->join('warehouse_zones', 'warehouse_aisles.zone_id', '=', 'warehouse_zones.id')
            ->where('warehouse_zones.warehouse_id', $warehouseId)
            ->where('warehouse_bins.is_active', true)
            ->select('warehouse_bins.*')
            ->withCount(['productWarehouses'])
            ->orderBy('warehouse_zones.sequence')
            ->orderBy('warehouse_aisles.sequence')
            ->orderBy('warehouse_racks.sequence')
            ->orderBy('warehouse_shelves.sequence')
            ->orderBy('warehouse_bins.sequence')
            ->get();

        return $bins->map(function ($bin) {
            return [
                'id' => $bin->id,
                'code' => $bin->formatted_code,
                'full_path' => $bin->full_location_path,
                'type' => $bin->type,
                'product_count' => $bin->product_warehouses_count,
                'capacity' => $bin->max_items,
            ];
        })->toArray();
    }

    /**
     * Log warehouse activity
     */
    private function logActivity(
        int $warehouseId,
        int $userId,
        string $activityType,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $newValues = null,
        ?array $oldValues = null,
        ?string $locationPath = null
    ): void {
        try {
            DB::table('warehouse_activity_logs')->insert([
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'activity_type' => $activityType,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'location_path' => $locationPath,
                'description' => $description,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log warehouse activity', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate zone code
     */
    private function generateZoneCode(int $warehouseId, string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
        $count = WarehouseZone::where('warehouse_id', $warehouseId)->count() + 1;
        return sprintf('Z-%s-%03d', $prefix, $count);
    }

    /**
     * Generate aisle code
     */
    private function generateAisleCode(int $zoneId, string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 2));
        $count = WarehouseAisle::where('zone_id', $zoneId)->count() + 1;
        return sprintf('A-%s-%02d', $prefix, $count);
    }

    /**
     * Generate rack code
     */
    private function generateRackCode(int $aisleId, string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 2));
        $count = WarehouseRack::where('aisle_id', $aisleId)->count() + 1;
        return sprintf('R-%s-%02d', $prefix, $count);
    }

    /**
     * Generate shelf code
     */
    private function generateShelfCode(int $rackId, string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 1));
        $count = WarehouseShelf::where('rack_id', $rackId)->count() + 1;
        return sprintf('S-%s-%01d', $prefix, $count);
    }

    /**
     * Generate bin code
     */
    private function generateBinCode(int $shelfId, string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 2));
        $count = WarehouseBin::where('shelf_id', $shelfId)->count() + 1;
        return sprintf('B-%s-%02d', $prefix, $count);
    }
}
