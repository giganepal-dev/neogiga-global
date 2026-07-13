<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single read model for sellability. It deliberately composes existing price,
 * warehouse and stock tables; it does not maintain another stock total.
 */
class ProductAvailabilityService
{
    public function __construct(private readonly RegionalVisibilityService $regional)
    {
    }

    /** @return array<string, mixed> */
    public function forProduct(Product|int $product, ?Marketplace $marketplace, ?int $variantId = null): array
    {
        $product = $product instanceof Product ? $product : Product::findOrFail($product);
        $stockRows = $this->regional->stockRows($product->id, $marketplace, $variantId);
        $globalStock = $this->globalAvailable($product->id, $variantId);
        $localStock = $marketplace?->country_id
            ? (int) $stockRows->where('country_id', $marketplace->country_id)->sum('quantity_available')
            : 0;
        $available = (int) $stockRows->sum('quantity_available');
        $fulfilment = $stockRows->first(fn ($row) => (int) $row->quantity_available > 0) ?: $stockRows->first();
        $price = $this->price($product, $marketplace, $variantId);
        $quoteOnly = $stockRows->contains(fn ($row) => (bool) ($row->quote_only ?? false));

        return [
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'marketplace_id' => $marketplace?->id,
            'marketplace' => strtolower((string) ($marketplace?->code ?? 'global')),
            'currency' => $price['currency'],
            'price' => $price,
            'price_source' => $price['source'],
            'local_stock' => $localStock,
            'regional_stock' => max(0, $available - $localStock),
            'global_stock' => $globalStock,
            'available_stock' => $available,
            'fulfilment_warehouse_id' => $fulfilment->warehouse_id ?? null,
            'fulfilment_warehouse_name' => $fulfilment->warehouse_name ?? null,
            'fulfilment_country_id' => $fulfilment->country_id ?? null,
            'fulfilment_country_name' => $fulfilment->country_name ?? null,
            'lead_time_days' => null,
            'quote_only' => $quoteOnly,
            'can_purchase' => ! $quoteOnly && $available > 0 && ($price['selling_price'] ?? 0) > 0,
            // Internal consumers may use these records; public JSON endpoints
            // remove them before returning a response.
            'stock_rows' => $stockRows,
            'marketplace_price' => $price['marketplace_price'],
            'seller_offers' => $price['seller_offers'],
        ];
    }

    /** @param array<string, mixed> $availability @return array<string, mixed> */
    public function publicPayload(array $availability): array
    {
        unset($availability['stock_rows'], $availability['marketplace_price'], $availability['seller_offers']);

        return $availability;
    }

    private function globalAvailable(int $productId, ?int $variantId): int
    {
        if (! Schema::hasTable('inventory_stocks')) {
            return 0;
        }

        return (int) DB::table('inventory_stocks as s')
            ->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->where('s.product_id', $productId)
            ->when($variantId !== null, fn ($query) => $query->where('s.variant_id', $variantId), fn ($query) => $query->whereNull('s.variant_id'))
            ->where('s.is_active', true)
            ->when(Schema::hasColumn('warehouses', 'is_active'), fn ($query) => $query->where('w.is_active', true))
            ->sum('s.quantity_available');
    }

    /** @return array<string, mixed> */
    private function price(Product $product, ?Marketplace $marketplace, ?int $variantId): array
    {
        $overlay = $this->regional->marketplacePrice($product->id, $marketplace, $variantId);
        $offers = $this->regional->sellerOffers($product->id, $marketplace, $variantId);
        $currency = $marketplace?->currency?->code ?? 'USD';

        if ($overlay) {
            return [
                'base_price' => (float) $overlay->base_price,
                'selling_price' => (float) ($overlay->sale_price ?? $overlay->base_price),
                'currency' => $overlay->currency_code ?: $currency,
                'tax_rate' => $overlay->tax_rate !== null ? (float) $overlay->tax_rate : null,
                'tax_inclusive' => (bool) ($overlay->is_tax_inclusive ?? false),
                'source' => 'marketplace_product_prices',
                'marketplace_price' => $overlay,
                'seller_offers' => $offers,
            ];
        }

        if ($offers->isNotEmpty()) {
            $offer = $offers->first();

            return [
                'base_price' => (float) $offer->selling_price,
                'selling_price' => (float) $offer->selling_price,
                'currency' => $offer->currency_code ?: $currency,
                'tax_rate' => null,
                'tax_inclusive' => false,
                'source' => 'vendor_product_prices',
                'marketplace_price' => null,
                'seller_offers' => $offers,
            ];
        }

        // The canonical product price is the final fallback already used by
        // the API cart. Regional storefronts may still present RFQ messaging
        // at the UI layer when no local overlay exists.
        $basePrice = (float) ($product->sale_price ?: $product->base_price ?: 0);

        return [
            'base_price' => $basePrice,
            'selling_price' => $basePrice,
            'currency' => $currency,
            'tax_rate' => null,
            'tax_inclusive' => false,
            'source' => 'products.base_price',
            'marketplace_price' => null,
            'seller_offers' => $offers,
        ];
    }
}
