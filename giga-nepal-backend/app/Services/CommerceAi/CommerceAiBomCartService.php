<?php

namespace App\Services\CommerceAi;

use App\Models\CommerceAi\CommerceAiBomResult;
use App\Models\Marketplace\Cart;
use App\Models\Marketplace\InventoryStock;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceProductPrice;
use App\Models\Marketplace\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommerceAiBomCartService
{
    /**
     * Adds only catalog-matched, purchasable recommendations belonging to the
     * authenticated user. Every price and stock value is resolved server-side.
     */
    public function addToCart(User $user, int $bomResultId, ?int $requestedMarketplaceId = null): ?array
    {
        $bom = CommerceAiBomResult::query()
            ->with(['bomRequest', 'items'])
            ->find($bomResultId);

        if (! $bom || ! $bom->bomRequest || (int) $bom->bomRequest->user_id !== (int) $user->id) {
            return null;
        }

        return DB::transaction(function () use ($user, $bom, $requestedMarketplaceId) {
            $cart = Cart::query()
                ->active()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $marketplaceId = $requestedMarketplaceId ?? $cart?->marketplace_id ?? $this->defaultMarketplaceId();
            if (! $marketplaceId) {
                throw ValidationException::withMessages([
                    'marketplace_id' => 'No active marketplace is available for this cart.',
                ]);
            }

            if ($cart && (int) $cart->marketplace_id !== (int) $marketplaceId) {
                return [
                    'marketplace_conflict' => true,
                    'cart_marketplace_id' => (int) $cart->marketplace_id,
                ];
            }

            $cart ??= Cart::create([
                'user_id' => $user->id,
                'marketplace_id' => $marketplaceId,
                'currency_code' => Marketplace::find($marketplaceId)?->currency?->code ?? 'USD',
                'expires_at' => now()->addDays(30),
                'is_active' => true,
            ]);

            $processedIds = collect($cart->metadata['commerce_ai_recommendation_item_ids'] ?? [])
                ->map(static fn ($id): int => (int) $id)
                ->all();
            $added = [];
            $skipped = [];
            $alreadyAdded = [];

            foreach ($bom->items as $item) {
                if (in_array($item->id, $processedIds, true)) {
                    $alreadyAdded[] = $this->itemSummary($item, 'already_added');
                    continue;
                }

                $quantity = (float) $item->quantity;
                if ($quantity < 1 || floor($quantity) !== $quantity || $quantity > 500) {
                    $skipped[] = $this->itemSummary($item, 'unsupported_quantity');
                    continue;
                }

                if (! $item->product_id) {
                    $skipped[] = $this->itemSummary($item, 'catalog_match_required');
                    continue;
                }

                $product = Product::query()->published()->find($item->product_id);
                if (! $product) {
                    $skipped[] = $this->itemSummary($item, 'product_not_purchasable');
                    continue;
                }

                $price = $this->currentPrice($product, $marketplaceId);
                if ($price['amount'] === null || $price['amount'] <= 0) {
                    $skipped[] = $this->itemSummary($item, 'active_price_required');
                    continue;
                }

                $existing = $cart->items()
                    ->where('product_id', $product->id)
                    ->whereNull('variant_id')
                    ->lockForUpdate()
                    ->first();
                $newQuantity = (int) ($existing?->quantity ?? 0) + (int) $quantity;

                if ($newQuantity > 500) {
                    $skipped[] = $this->itemSummary($item, 'cart_quantity_limit');
                    continue;
                }

                if (! $this->hasStock($product->id, $marketplaceId, $newQuantity)) {
                    $skipped[] = $this->itemSummary($item, 'regional_stock_unavailable');
                    continue;
                }

                $metadata = array_merge($existing?->metadata ?? [], [
                    'price_source' => $price['source'],
                    'commerce_ai' => [
                        'bom_result_id' => $bom->id,
                        'recommendation_item_id' => $item->id,
                        'source_notes' => 'Catalog-matched Commerce AI recommendation; price and availability were verified at cart mutation time.',
                        'confidence_level' => 'high',
                        'last_updated' => now()->toISOString(),
                        'advisory_disclaimer' => 'Advisory only',
                    ],
                ]);

                if ($existing) {
                    $existing->forceFill([
                        'quantity' => $newQuantity,
                        'unit_price' => $price['amount'],
                        'metadata' => $metadata,
                    ])->save();
                } else {
                    $cart->items()->create([
                        'product_id' => $product->id,
                        'quantity' => (int) $quantity,
                        'unit_price' => $price['amount'],
                        'tax_rate' => 0,
                        'tax_amount' => 0,
                        'discount_amount' => 0,
                        'metadata' => $metadata,
                    ]);
                }

                $processedIds[] = (int) $item->id;
                $added[] = $this->itemSummary($item, 'added', $product->id, (int) $quantity);
            }

            $cart->forceFill([
                'metadata' => array_merge($cart->metadata ?? [], [
                    'commerce_ai_recommendation_item_ids' => array_values(array_unique($processedIds)),
                ]),
            ])->save();
            $cart->calculateTotal();

            return [
                'cart' => $this->publicCart($cart->fresh()),
                'bom_result_id' => $bom->id,
                'added' => $added,
                'added_count' => count($added),
                'already_added' => $alreadyAdded,
                'already_added_count' => count($alreadyAdded),
                'skipped' => $skipped,
                'skipped_count' => count($skipped),
                'source_notes' => 'Only user-owned, catalog-matched products with a current server-side price and sufficient regional inventory were added.',
                'confidence_level' => 'high',
                'last_updated' => now()->toISOString(),
                'disclaimer' => 'Advisory only. Cart contents are not an order, payment, or stock reservation.',
            ];
        });
    }

    private function defaultMarketplaceId(): ?int
    {
        return Marketplace::query()
            ->where('is_default', true)
            ->value('id')
            ?? Marketplace::query()->value('id');
    }

    private function currentPrice(Product $product, int $marketplaceId): array
    {
        $price = MarketplaceProductPrice::query()
            ->where('product_id', $product->id)
            ->where('marketplace_id', $marketplaceId)
            ->where('is_active', true)
            ->first();

        if ($price) {
            return [
                'amount' => (float) ($price->sale_price ?? $price->base_price),
                'source' => 'marketplace_product_prices',
            ];
        }

        return [
            'amount' => (float) ($product->sale_price ?? $product->base_price),
            'source' => 'products.base_price',
        ];
    }

    private function hasStock(int $productId, int $marketplaceId, int $quantity): bool
    {
        $available = InventoryStock::query()
            ->where('product_id', $productId)
            ->where('marketplace_id', $marketplaceId)
            ->where('is_active', true)
            ->sum('quantity_available');

        return (int) $available >= $quantity;
    }

    private function publicCart(Cart $cart): Cart
    {
        $cart->calculateTotal();
        $cart->refresh()->load([
            'items.product' => fn ($products) => $products->published(),
            'items.variant',
        ]);
        $cart->setRelation('items', $cart->items->filter(fn ($item) => $item->product !== null)->values());

        return $cart;
    }

    private function itemSummary(object $item, string $status, ?int $productId = null, ?int $quantity = null): array
    {
        return array_filter([
            'recommendation_item_id' => $item->id,
            'product_id' => $productId ?? $item->product_id,
            'name' => $item->name,
            'quantity' => $quantity ?? (float) $item->quantity,
            'status' => $status,
        ], static fn ($value): bool => $value !== null);
    }
}
