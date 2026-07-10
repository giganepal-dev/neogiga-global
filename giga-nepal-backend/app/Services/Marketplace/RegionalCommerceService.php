<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Cart;
use App\Models\Marketplace\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RegionalCommerceService
{
    public function applyCartEstimates(Cart $cart, ?int $countryId = null, ?int $regionId = null): Cart
    {
        $cart->loadMissing('items.product');

        $marketplaceId = $cart->marketplace_id;
        $countryId = $countryId ?: (int) data_get($cart->metadata, 'country_id');
        $taxZone = $this->taxZone($marketplaceId, $countryId, $regionId);
        $deliveryZone = $this->deliveryZone($marketplaceId, $countryId, $regionId);
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $discountTotal = 0.0;

        foreach ($cart->items as $item) {
            $lineSubtotal = (float) $item->unit_price * (int) $item->quantity;
            $taxAmount = $this->lineTax($lineSubtotal, $taxZone);
            $discount = (float) $item->discount_amount;
            $metadata = array_merge((array) $item->metadata, [
                'tax_zone_id' => $taxZone?->id,
                'tax_zone_code' => $taxZone?->code,
                'delivery_zone_id' => $deliveryZone?->id,
                'delivery_zone_code' => $deliveryZone?->code,
                'warehouse_route' => $this->bestWarehouseRoute((int) $item->product_id, (int) $item->quantity, $marketplaceId, $countryId),
            ]);

            $item->forceFill([
                'tax_rate' => $taxZone ? (float) $taxZone->tax_rate : 0,
                'tax_amount' => $taxAmount,
                'subtotal' => $lineSubtotal,
                'total' => $lineSubtotal + $taxAmount - $discount,
                'metadata' => $metadata,
            ])->save();

            $subtotal += $lineSubtotal;
            $taxTotal += $taxAmount;
            $discountTotal += $discount;
        }

        $shipping = $this->shippingEstimate($subtotal, $deliveryZone);

        $cart->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'shipping_total' => $shipping,
            'grand_total' => $subtotal + $taxTotal + $shipping - $discountTotal,
            'item_count' => $cart->items->sum(fn ($item) => (int) $item->quantity),
            'metadata' => array_merge((array) $cart->metadata, [
                'tax_zone_id' => $taxZone?->id,
                'tax_zone_code' => $taxZone?->code,
                'tax_rate' => $taxZone ? (float) $taxZone->tax_rate : 0,
                'delivery_zone_id' => $deliveryZone?->id,
                'delivery_zone_code' => $deliveryZone?->code,
                'estimated_delivery_days' => $deliveryZone ? [
                    'min' => $deliveryZone->estimated_days_min,
                    'max' => $deliveryZone->estimated_days_max,
                ] : null,
                'regional_estimate_note' => 'Advisory estimate only; staff confirms taxes, shipping, stock, and payment before fulfillment.',
            ]),
        ])->save();

        return $cart->refresh();
    }

    public function cartRoutes(Cart $cart): Collection
    {
        $cart->loadMissing('items.product');

        return $cart->items->map(function ($item) {
            $route = (array) data_get($item->metadata, 'warehouse_route', []);

            return [
                'product_name' => $item->product?->name,
                'sku' => $item->product?->sku,
                'quantity' => (int) $item->quantity,
                'warehouse_name' => $route['warehouse_name'] ?? null,
                'warehouse_code' => $route['warehouse_code'] ?? null,
                'country_name' => $route['country_name'] ?? null,
                'available' => $route['available'] ?? 0,
                'status' => $route['status'] ?? 'quote_required',
            ];
        });
    }

    public function productRegionalSummary(Product $product, ?int $marketplaceId = null, ?int $countryId = null): array
    {
        return $this->bestWarehouseRoute($product->id, 1, $marketplaceId, $countryId);
    }

    private function taxZone(?int $marketplaceId, ?int $countryId, ?int $regionId): ?object
    {
        return DB::table('tax_zones')
            ->where('is_active', true)
            ->when($marketplaceId, fn ($query) => $query->where(fn ($q) => $q->where('marketplace_id', $marketplaceId)->orWhereNull('marketplace_id')))
            ->when($countryId, fn ($query) => $query->where(fn ($q) => $q->where('country_id', $countryId)->orWhereNull('country_id')))
            ->when($regionId, fn ($query) => $query->where(fn ($q) => $q->where('region_id', $regionId)->orWhereNull('region_id')))
            ->orderByDesc('priority')
            ->orderByRaw('CASE WHEN region_id IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN country_id IS NULL THEN 1 ELSE 0 END')
            ->first();
    }

    private function deliveryZone(?int $marketplaceId, ?int $countryId, ?int $regionId): ?object
    {
        return DB::table('delivery_zones')
            ->where('is_active', true)
            ->when($marketplaceId, fn ($query) => $query->where(fn ($q) => $q->where('marketplace_id', $marketplaceId)->orWhereNull('marketplace_id')))
            ->when($countryId, fn ($query) => $query->where(fn ($q) => $q->where('country_id', $countryId)->orWhereNull('country_id')))
            ->when($regionId, fn ($query) => $query->where(fn ($q) => $q->where('region_id', $regionId)->orWhereNull('region_id')))
            ->orderByRaw('CASE WHEN region_id IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN country_id IS NULL THEN 1 ELSE 0 END')
            ->first();
    }

    private function lineTax(float $lineSubtotal, ?object $taxZone): float
    {
        if (! $taxZone || (float) $taxZone->tax_rate <= 0) {
            return 0.0;
        }

        if ((bool) $taxZone->is_inclusive) {
            return 0.0;
        }

        return round($lineSubtotal * ((float) $taxZone->tax_rate / 100), 2);
    }

    private function shippingEstimate(float $subtotal, ?object $deliveryZone): float
    {
        if (! $deliveryZone) {
            return 0.0;
        }

        $freeThreshold = (float) ($deliveryZone->free_shipping_threshold ?? 0);
        if ($freeThreshold > 0 && $subtotal >= $freeThreshold) {
            return 0.0;
        }

        return round((float) ($deliveryZone->base_fee ?? 0), 2);
    }

    private function bestWarehouseRoute(int $productId, int $quantity, ?int $marketplaceId, ?int $countryId): array
    {
        $stock = DB::table('inventory_stocks as s')
            ->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->leftJoin('countries as c', 'c.id', '=', 's.country_id')
            ->where('s.product_id', $productId)
            ->where('s.is_active', true)
            ->when($marketplaceId, fn ($query) => $query->where(fn ($q) => $q->where('s.marketplace_id', $marketplaceId)->orWhereNull('s.marketplace_id')))
            ->select('s.id', 's.quantity_available', 's.quote_only', 's.country_id', 'w.id as warehouse_id', 'w.name as warehouse_name', 'w.code as warehouse_code', 'c.name as country_name')
            ->when($countryId, fn ($query) => $query->orderByRaw('CASE WHEN s.country_id = ? THEN 0 ELSE 1 END', [$countryId]))
            ->orderByDesc('s.quantity_available')
            ->first();

        if (! $stock) {
            return ['status' => 'quote_required', 'available' => 0];
        }

        $available = (int) $stock->quantity_available;

        return [
            'stock_id' => $stock->id,
            'warehouse_id' => $stock->warehouse_id,
            'warehouse_name' => $stock->warehouse_name,
            'warehouse_code' => $stock->warehouse_code,
            'country_id' => $stock->country_id,
            'country_name' => $stock->country_name,
            'available' => $available,
            'status' => (bool) $stock->quote_only ? 'quote_only' : ($available >= $quantity ? 'available' : 'partial_or_rfq'),
        ];
    }
}
