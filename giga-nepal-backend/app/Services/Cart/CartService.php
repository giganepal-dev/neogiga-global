<?php

namespace App\Services\Cart;

use App\Models\Marketplace\Cart;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\User;
use App\Services\Marketplace\ProductAvailabilityService;
use Illuminate\Support\Facades\DB;

/**
 * Server-authoritative cart operations shared by storefront, BOM and AI flows.
 */
class CartService
{
    public function __construct(private readonly ProductAvailabilityService $availability)
    {
    }

    /** @return array{ok:bool, reason?:string, cart?:Cart} */
    public function addProduct(
        User $user,
        int $productId,
        int $quantity,
        ?int $marketplaceId = null,
        ?int $variantId = null,
    ): array {
        $product = Product::query()->published()->find($productId);
        if (! $product) {
            return ['ok' => false, 'reason' => 'Product is not available for purchase.'];
        }

        $marketplaceId ??= $this->defaultMarketplaceId();
        $marketplace = Marketplace::find($marketplaceId);
        $availability = $this->availability->forProduct($product, $marketplace, $variantId);
        $price = $availability['price'];

        if (($price['selling_price'] ?? 0) <= 0) {
            return ['ok' => false, 'reason' => 'Product has no active server-side price.'];
        }

        if (($availability['quote_only'] ?? false) || (int) $availability['available_stock'] < $quantity) {
            return ['ok' => false, 'reason' => 'Requested quantity is not available in regional inventory.'];
        }

        return DB::transaction(function () use ($user, $product, $quantity, $marketplaceId, $marketplace, $variantId) {
            $current = $this->availability->forProduct($product, $marketplace, $variantId);
            $price = $current['price'];
            $cart = $this->activeCart($user, $marketplaceId, $price['currency']);

            $existing = $cart->items()
                ->where('product_id', $product->id)
                ->where('variant_id', $variantId)
                ->first();

            $newQuantity = ($existing?->quantity ?? 0) + $quantity;
            if (($current['quote_only'] ?? false) || (int) $current['available_stock'] < $newQuantity) {
                return ['ok' => false, 'reason' => 'Requested quantity is not available in regional inventory.'];
            }

            if ($existing) {
                $existing->forceFill([
                    'quantity' => $newQuantity,
                    'unit_price' => $price['selling_price'],
                    'metadata' => ['price_source' => $price['source']],
                ])->save();
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'unit_price' => $price['selling_price'],
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'metadata' => ['price_source' => $price['source']],
                ]);
            }

            $cart->calculateTotal();

            return ['ok' => true, 'cart' => $cart->fresh()];
        });
    }

    public function activeCart(User $user, ?int $marketplaceId = null, ?string $currencyCode = null): Cart
    {
        $marketplaceId ??= $this->defaultMarketplaceId();
        $currencyCode ??= Marketplace::find($marketplaceId)?->currency?->code ?? 'USD';

        return Cart::firstOrCreate(
            ['user_id' => $user->id, 'is_active' => true],
            [
                'marketplace_id' => $marketplaceId,
                'currency_code' => $currencyCode,
                'expires_at' => now()->addDays(30),
            ],
        );
    }

    public function defaultMarketplaceId(): ?int
    {
        return Marketplace::query()->where('is_default', true)->value('id')
            ?? Marketplace::query()->value('id');
    }

    /** @return array{amount:?float, currency:string, source:string} */
    public function currentPrice(Product $product, ?int $marketplaceId, ?int $variantId = null): array
    {
        $availability = $this->availability->forProduct($product, Marketplace::find($marketplaceId), $variantId);
        $price = $availability['price'];

        return [
            'amount' => (float) ($price['selling_price'] ?? 0),
            'currency' => $price['currency'],
            'source' => $price['source'],
        ];
    }

    public function hasStock(int $productId, ?int $marketplaceId, int $quantity, ?int $variantId = null): bool
    {
        $product = Product::find($productId);
        if (! $product) {
            return false;
        }

        $availability = $this->availability->forProduct($product, Marketplace::find($marketplaceId), $variantId);

        return ! ($availability['quote_only'] ?? false)
            && (int) $availability['available_stock'] >= $quantity;
    }
}
