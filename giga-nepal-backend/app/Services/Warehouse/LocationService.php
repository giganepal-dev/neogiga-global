<?php

namespace App\Services\Warehouse;

use App\Models\Marketplace\Warehouse;
use App\Models\Warehouse\{WarehouseZone, WarehouseAisle, WarehouseRack, WarehouseShelf, WarehouseBin};
use App\Models\Inventory\{InventoryBatch, SerialNumber, StockCount, StockCountItem};
use App\Models\Marketplace\{Product, ProductVariant, InventoryStock};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Warehouse Location Service
 * 
 * Manages warehouse location hierarchy and bin assignments
 */
class LocationService
{
    /**
     * Create a complete location structure for a warehouse
     */
    public function createLocationStructure(
        int $warehouseId,
        array $zones,
        bool $clearExisting = false
    ): array {
        return DB::transaction(function () use ($warehouseId, $zones, $clearExisting) {
            $warehouse = Warehouse::findOrFail($warehouseId);
            
            if ($clearExisting) {
                // Soft delete existing locations
                $warehouse->zones()->update(['deleted_at' => now()]);
            }

            $result = [
                'zones_created' => 0,
                'aisles_created' => 0,
                'racks_created' => 0,
                'shelves_created' => 0,
                'bins_created' => 0,
            ];

            foreach ($zones as $zoneData) {
                $zone = WarehouseZone::create([
                    'warehouse_id' => $warehouseId,
                    'name' => $zoneData['name'],
                    'code' => $zoneData['code'] ?? strtoupper(substr($zoneData['name'], 0, 3)),
                    'zone_type' => $zoneData['zone_type'] ?? 'storage',
                    'sort_order' => $zoneData['sort_order'] ?? 0,
                ]);
                $result['zones_created']++;

                foreach ($zoneData['aisles'] ?? [] as $aisleData) {
                    $aisle = WarehouseAisle::create([
                        'warehouse_zone_id' => $zone->id,
                        'name' => $aisleData['name'],
                        'code' => $aisleData['code'] ?? "A{$aisleData['aisle_number']}",
                        'aisle_number' => $aisleData['aisle_number'] ?? 0,
                        'length_meters' => $aisleData['length_meters'] ?? null,
                        'width_meters' => $aisleData['width_meters'] ?? null,
                        'sort_order' => $aisleData['sort_order'] ?? 0,
                    ]);
                    $result['aisles_created']++;

                    foreach ($aisleData['racks'] ?? [] as $rackData) {
                        $rack = WarehouseRack::create([
                            'warehouse_aisle_id' => $aisle->id,
                            'name' => $rackData['name'],
                            'code' => $rackData['code'] ?? "R{$rackData['rack_number']}",
                            'rack_number' => $rackData['rack_number'] ?? 0,
                            'levels' => $rackData['levels'] ?? 1,
                            'max_weight_kg' => $rackData['max_weight_kg'] ?? null,
                            'sort_order' => $rackData['sort_order'] ?? 0,
                        ]);
                        $result['racks_created']++;

                        for ($level = 1; $level <= $rack->levels; $level++) {
                            $shelf = WarehouseShelf::create([
                                'warehouse_rack_id' => $rack->id,
                                'name' => "Level {$level}",
                                'code' => "L{$level}",
                                'level_number' => $level,
                                'height_cm' => $rackData['shelf_height_cm'] ?? 30,
                                'depth_cm' => $rackData['shelf_depth_cm'] ?? 40,
                                'sort_order' => $level,
                            ]);
                            $result['shelves_created']++;

                            $binsPerShelf = $rackData['bins_per_shelf'] ?? 4;
                            for ($binNum = 1; $binNum <= $binsPerShelf; $binNum++) {
                                WarehouseBin::create([
                                    'warehouse_shelf_id' => $shelf->id,
                                    'name' => "Bin {$binNum}",
                                    'code' => "B" . str_pad($binNum, 3, '0', STR_PAD_LEFT),
                                    'bin_number' => $binNum,
                                    'bin_type' => $rackData['bin_type'] ?? 'standard',
                                    'sort_order' => $binNum,
                                ]);
                                $result['bins_created']++;
                            }
                        }
                    }
                }
            }

            Log::info('Warehouse location structure created', [
                'warehouse_id' => $warehouseId,
                'results' => $result,
            ]);

            return $result;
        });
    }

    /**
     * Get available bins in a warehouse
     */
    public function getAvailableBins(
        int $warehouseId,
        ?string $zoneType = null,
        ?string $binType = null
    ) {
        $query = WarehouseBin::whereHas('shelf.rack.aisle.zone', function ($q) use ($warehouseId, $zoneType) {
            $q->where('warehouse_id', $warehouseId)
              ->where('is_active', true);
            if ($zoneType) {
                $q->where('zone_type', $zoneType);
            }
        })
        ->where('is_active', true)
        ->where('is_locked', false);

        if ($binType) {
            $query->where('bin_type', $binType);
        }

        return $query->with(['shelf.rack.aisle.zone'])
            ->ordered()
            ->get();
    }

    /**
     * Find bin by location code
     */
    public function findBinByCode(string $locationCode): ?WarehouseBin
    {
        // Code format: ZONE-AISLE-RACK-LEVEL-BIN (e.g., STO-A01-R05-L02-B003)
        return WarehouseBin::where('code', $locationCode)->first();
    }

    /**
     * Assign product to a bin
     */
    public function assignToBin(
        int $inventoryStockId,
        int $binId,
        int $userId
    ): bool {
        return DB::transaction(function () use ($inventoryStockId, $binId, $userId) {
            $stock = InventoryStock::findOrFail($inventoryStockId);
            $bin = WarehouseBin::findOrFail($binId);

            // Verify bin is in same warehouse
            if ($stock->warehouse_id !== $bin->shelf->rack->aisle->zone->warehouse_id) {
                throw new \Exception('Bin must be in the same warehouse as the inventory stock');
            }

            $stock->update([
                'warehouse_bin_id' => $binId,
            ]);

            Log::info('Inventory assigned to bin', [
                'stock_id' => $inventoryStockId,
                'bin_id' => $binId,
                'user_id' => $userId,
            ]);

            return true;
        });
    }

    /**
     * Get location path for a bin
     */
    public function getLocationPath(int $binId): string
    {
        $bin = WarehouseBin::with(['shelf.rack.aisle.zone.warehouse'])->findOrFail($binId);
        return $bin->full_location;
    }

    /**
     * Generate bin labels for printing
     */
    public function generateBinLabels(array $binIds, string $template = 'standard'): array
    {
        $bins = WarehouseBin::with(['shelf.rack.aisle.zone.warehouse'])
            ->whereIn('id', $binIds)
            ->get();

        $labels = [];
        foreach ($bins as $bin) {
            $labels[] = [
                'bin_code' => $bin->code,
                'location_code' => $bin->location_code,
                'full_path' => $bin->full_location,
                'bin_type' => $bin->bin_type,
                'qr_data' => json_encode([
                    'bin_id' => $bin->id,
                    'code' => $bin->code,
                    'warehouse_id' => $bin->shelf->rack->aisle->zone->warehouse_id,
                ]),
            ];
        }

        return $labels;
    }

    /**
     * Get warehouse capacity utilization
     */
    public function getCapacityUtilization(int $warehouseId): array
    {
        $warehouse = Warehouse::with(['zones.aisles.racks.shelves.bins'])->findOrFail($warehouseId);
        
        $totalBins = 0;
        $occupiedBins = 0;

        foreach ($warehouse->zones as $zone) {
            foreach ($zone->aisles as $aisle) {
                foreach ($aisle->racks as $rack) {
                    foreach ($rack->shelves as $shelf) {
                        foreach ($shelf->bins as $bin) {
                            $totalBins++;
                            if ($bin->inventoryStocks()->count() > 0) {
                                $occupiedBins++;
                            }
                        }
                    }
                }
            }
        }

        return [
            'total_bins' => $totalBins,
            'occupied_bins' => $occupiedBins,
            'available_bins' => $totalBins - $occupiedBins,
            'utilization_percentage' => $totalBins > 0 
                ? round(($occupiedBins / $totalBins) * 100, 2) 
                : 0,
        ];
    }
}
