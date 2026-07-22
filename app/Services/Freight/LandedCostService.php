<?php

namespace App\Services\Freight;

use App\Models\Freight\FreightShipment;
use App\Models\Freight\FreightExpense;
use App\Models\Freight\LandedCostAllocation;
use App\Models\Purchasing\PurchaseOrder;
use App\Models\Warehouse\Warehouse;
use Illuminate\Support\Facades\DB;

class LandedCostService
{
    /**
     * Allocate landed costs across products in a shipment
     */
    public function allocateLandedCost(
        FreightShipment $shipment,
        string $method = 'weight'
    ): array {
        return DB::transaction(function () use ($shipment, $method) {
            $allocations = [];
            $totalBasis = 0;
            $totalCost = $shipment->total_cost;

            // Get all purchase orders linked to this shipment
            $purchaseOrders = PurchaseOrder::whereHas('items', function ($query) use ($shipment) {
                $query->where('warehouse_id', $shipment->warehouse_id);
            })->get();

            // Calculate allocation basis for each product
            foreach ($purchaseOrders as $po) {
                foreach ($po->items as $item) {
                    $product = $item->product;
                    $basis = $this->calculateBasis($product, $item->quantity, $method, $shipment);
                    
                    if ($basis > 0) {
                        $totalBasis += $basis;
                        
                        $allocations[] = [
                            'freight_shipment_id' => $shipment->id,
                            'purchase_order_id' => $po->id,
                            'product_id' => $product->id,
                            'warehouse_id' => $shipment->warehouse_id,
                            'quantity' => $item->quantity,
                            'basis' => $basis,
                            'original_cost' => $item->unit_price * $item->quantity,
                        ];
                    }
                }
            }

            if ($totalBasis == 0 || count($allocations) == 0) {
                return ['success' => false, 'message' => 'No allocatable items found'];
            }

            // Calculate allocated cost for each item
            foreach ($allocations as &$allocation) {
                $ratio = $allocation['basis'] / $totalBasis;
                $allocatedCost = $totalCost * $ratio;
                
                $allocation['allocated_cost'] = $allocatedCost;
                $allocation['total_landed_cost'] = $allocation['original_cost'] + $allocatedCost;
                $allocation['cost_per_unit'] = $allocation['total_landed_cost'] / $allocation['quantity'];
                $allocation['allocation_method'] = $method;
                $allocation['currency'] = $shipment->currency;
                $allocation['posted_to_inventory'] = false;
            }

            // Save allocations
            foreach ($allocations as $data) {
                LandedCostAllocation::create($data);
            }

            return [
                'success' => true,
                'allocations' => count($allocations),
                'total_cost' => $totalCost,
                'total_basis' => $totalBasis,
            ];
        });
    }

    /**
     * Calculate allocation basis based on method
     */
    private function calculateBasis(
        $product,
        float $quantity,
        string $method,
        FreightShipment $shipment
    ): float {
        switch ($method) {
            case 'weight':
                return ($product->weight ?? 0) * $quantity;
            
            case 'volume':
                $volume = ($product->length ?? 0) * ($product->width ?? 0) * ($product->height ?? 0);
                return $volume * $quantity;
            
            case 'value':
                return ($product->cost_price ?? 0) * $quantity;
            
            case 'quantity':
            default:
                return $quantity;
        }
    }

    /**
     * Post landed costs to inventory valuation
     */
    public function postToInventory(int $allocationId): bool
    {
        return DB::transaction(function () use ($allocationId) {
            $allocation = LandedCostAllocation::findOrFail($allocationId);
            
            if ($allocation->posted_to_inventory) {
                return false;
            }

            // Update product warehouse cost
            $productWarehouse = \App\Models\Warehouse\ProductWarehouse::firstOrCreate(
                [
                    'product_id' => $allocation->product_id,
                    'warehouse_id' => $allocation->warehouse_id,
                ],
                ['available_stock' => 0, 'cost_price' => 0]
            );

            // Recalculate average cost including landed cost
            $currentStock = $productWarehouse->available_stock;
            $currentCost = $productWarehouse->cost_price;
            $totalCurrentValue = $currentStock * $currentCost;
            
            $newStock = $currentStock + $allocation->quantity;
            $newValue = $totalCurrentValue + $allocation->total_landed_cost;
            
            $productWarehouse->cost_price = $newStock > 0 ? $newValue / $newStock : 0;
            $productWarehouse->save();

            // Mark allocation as posted
            $allocation->posted_to_inventory = true;
            $allocation->posted_at = now();
            $allocation->save();

            // Create inventory movement record
            \App\Models\Warehouse\InventoryMovement::create([
                'product_id' => $allocation->product_id,
                'warehouse_id' => $allocation->warehouse_id,
                'movement_type' => 'cost_adjustment',
                'quantity' => 0,
                'reference_type' => LandedCostAllocation::class,
                'reference_id' => $allocation->id,
                'notes' => "Landed cost adjustment: {$allocation->allocated_cost}",
                'user_id' => auth()->id(),
            ]);

            return true;
        });
    }

    /**
     * Post all unposted allocations for a shipment
     */
    public function postAllForShipment(FreightShipment $shipment): array
    {
        $unposted = LandedCostAllocation::where('freight_shipment_id', $shipment->id)
            ->where('posted_to_inventory', false)
            ->get();

        $success = 0;
        $failed = 0;

        foreach ($unposted as $allocation) {
            try {
                if ($this->postToInventory($allocation->id)) {
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'total' => $unposted->count(),
        ];
    }
}
