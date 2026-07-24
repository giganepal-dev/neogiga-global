<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Cart;
use App\Models\Marketplace\InventoryStock;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceProductPrice;
use App\Models\Marketplace\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        return $this->success($this->publicCart($this->activeCart($request)));
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['sometimes', 'nullable', 'integer', 'exists:product_variants,id'],
            'marketplace_id' => ['sometimes', 'nullable', 'integer', 'exists:marketplaces,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $product = Product::query()->published()->find($validated['product_id']);

        if (! $product) {
            return $this->error('Product is not available for purchase.', 422);
        }

        $marketplaceId = $validated['marketplace_id'] ?? $this->defaultMarketplaceId();
        $price = $this->currentPrice($product, $marketplaceId);

        if ($price['amount'] === null || $price['amount'] <= 0) {
            return $this->error('Product has no active server-side price.', 422);
        }

        return DB::transaction(function () use ($request, $validated, $marketplaceId, $price) {
            $cart = $this->activeCart($request, $marketplaceId, $price['currency']);
            $variantId = $validated['variant_id'] ?? null;

            $existing = $cart->items()
                ->where('product_id', $validated['product_id'])
                ->where('variant_id', $variantId)
                ->first();

            $newQuantity = ($existing?->quantity ?? 0) + $validated['quantity'];
            if (! $this->hasStock($validated['product_id'], $marketplaceId, $newQuantity, $variantId)) {
                return $this->error('Requested quantity is not available in regional inventory.', 422);
            }

            if ($existing) {
                $existing->forceFill([
                    'quantity' => $newQuantity,
                    'unit_price' => $price['amount'],
                    'metadata' => ['price_source' => $price['source']],
                ])->save();
            } else {
                $cart->items()->create([
                    'product_id' => $validated['product_id'],
                    'variant_id' => $variantId,
                    'quantity' => $validated['quantity'],
                    'unit_price' => $price['amount'],
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'metadata' => ['price_source' => $price['source']],
                ]);
            }

            $cart->calculateTotal();

            return $this->success($this->publicCart($cart->fresh()), 201);
        });
    }

    public function addBom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $marketplaceId = $request->input('marketplace_id') ?? $this->defaultMarketplaceId();
        $cart = $this->activeCart($request, $marketplaceId);

        $added = 0;
        $errors = [];

        foreach ($validated['items'] as $bomItem) {
            $product = Product::query()->published()->find($bomItem['product_id']);
            if (! $product) {
                $errors[] = ['product_id' => $bomItem['product_id'], 'error' => 'Product not available'];
                continue;
            }

            $price = $this->currentPrice($product, $marketplaceId);
            if ($price['amount'] === null || $price['amount'] <= 0) {
                $errors[] = ['product_id' => $bomItem['product_id'], 'error' => 'No active price'];
                continue;
            }

            $existing = $cart->items()
                ->where('product_id', $bomItem['product_id'])
                ->first();

            $newQuantity = ($existing?->quantity ?? 0) + $bomItem['quantity'];
            if (! $this->hasStock($bomItem['product_id'], $marketplaceId, $newQuantity)) {
                $errors[] = ['product_id' => $bomItem['product_id'], 'error' => 'Insufficient stock'];
                continue;
            }

            if ($existing) {
                $existing->forceFill([
                    'quantity' => $newQuantity,
                    'unit_price' => $price['amount'],
                ])->save();
            } else {
                $cart->items()->create([
                    'product_id' => $bomItem['product_id'],
                    'quantity' => $bomItem['quantity'],
                    'unit_price' => $price['amount'],
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'metadata' => ['price_source' => $price['source'], 'source' => 'bom'],
                ]);
            }

            $added++;
        }

        $cart->calculateTotal();

        return $this->success([
            'cart_id' => $cart->id,
            'items_added' => $added,
            'errors' => $errors,
            'cart' => $this->publicCart($cart->fresh()),
        ]);
    }

    public function updateItem(Request $request, int $item): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $cart = $this->activeCart($request);
        $cartItem = $cart->items()->whereKey($item)->first();

        if (! $cartItem) {
            return $this->error('Cart item not found.', 404);
        }

        if (! Product::published()->whereKey($cartItem->product_id)->exists()) {
            return $this->error('Cart product is no longer publicly available.', 422);
        }

        if (! $this->hasStock($cartItem->product_id, $cart->marketplace_id, $validated['quantity'], $cartItem->variant_id)) {
            return $this->error('Requested quantity is not available in regional inventory.', 422);
        }

        $cartItem->forceFill(['quantity' => $validated['quantity']])->save();
        $cart->calculateTotal();

        return $this->success($this->publicCart($cart->fresh()));
    }

    public function removeItem(Request $request, int $item): JsonResponse
    {
        $cart = $this->activeCart($request);
        $deleted = $cart->items()->whereKey($item)->delete();

        if (! $deleted) {
            return $this->error('Cart item not found.', 404);
        }

        $cart->calculateTotal();

        return $this->success($this->publicCart($cart->fresh()));
    }

    private function activeCart(Request $request, ?int $marketplaceId = null, ?string $currencyCode = null): Cart
    {
        $marketplaceId ??= $this->defaultMarketplaceId();
        $currencyCode ??= Marketplace::find($marketplaceId)?->currency?->code ?? 'USD';

        return Cart::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'is_active' => true,
            ],
            [
                'marketplace_id' => $marketplaceId,
                'currency_code' => $currencyCode,
                'expires_at' => now()->addDays(30),
            ],
        );
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

    private function defaultMarketplaceId(): ?int
    {
        return Marketplace::query()
            ->where('is_default', true)
            ->value('id')
            ?? Marketplace::query()->value('id');
    }

    private function currentPrice(Product $product, ?int $marketplaceId): array
    {
        $price = MarketplaceProductPrice::query()
            ->where('product_id', $product->id)
            ->when($marketplaceId, fn ($q) => $q->where('marketplace_id', $marketplaceId))
            ->where('is_active', true)
            ->first();

        if ($price) {
            return [
                'amount' => (float) ($price->sale_price ?? $price->base_price),
                'currency' => $price->currency_code,
                'source' => 'marketplace_product_prices',
            ];
        }

        return [
            'amount' => (float) ($product->sale_price ?? $product->base_price),
            'currency' => 'USD',
            'source' => 'products.base_price',
        ];
    }

    private function hasStock(int $productId, ?int $marketplaceId, int $quantity, ?int $variantId = null): bool
    {
        $available = InventoryStock::query()
            ->where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('variant_id', $variantId))
            ->when($marketplaceId, fn ($q) => $q->where('marketplace_id', $marketplaceId))
            ->sum('quantity_available');

        return (int) $available >= $quantity;
    }
}
