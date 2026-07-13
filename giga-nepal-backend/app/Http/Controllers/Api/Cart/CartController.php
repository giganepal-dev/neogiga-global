<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly CartService $cart)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->success($this->cart->activeCart($request->user())->load(['items.product', 'items.variant']));
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['sometimes', 'nullable', 'integer', 'exists:product_variants,id'],
            'marketplace_id' => ['sometimes', 'nullable', 'integer', 'exists:marketplaces,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $result = $this->cart->addProduct(
            $request->user(),
            $validated['product_id'],
            $validated['quantity'],
            $validated['marketplace_id'] ?? null,
            $validated['variant_id'] ?? null,
        );

        if (! $result['ok']) {
            return $this->error($result['reason'], 422);
        }

        return $this->success($result['cart']->load(['items.product', 'items.variant']), 201);
    }

    public function addBom(): JsonResponse
    {
        return $this->notImplemented('AI BOM to cart', 'Phase 2');
    }

    public function updateItem(Request $request, int $item): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $cart = $this->cart->activeCart($request->user());
        $cartItem = $cart->items()->whereKey($item)->first();

        if (! $cartItem) {
            return $this->error('Cart item not found.', 404);
        }

        if (! $this->cart->hasStock($cartItem->product_id, $cart->marketplace_id, $validated['quantity'], $cartItem->variant_id)) {
            return $this->error('Requested quantity is not available in regional inventory.', 422);
        }

        $cartItem->forceFill(['quantity' => $validated['quantity']])->save();
        $cart->calculateTotal();

        return $this->success($cart->fresh()->load(['items.product', 'items.variant']));
    }

    public function removeItem(Request $request, int $item): JsonResponse
    {
        $cart = $this->cart->activeCart($request->user());
        $deleted = $cart->items()->whereKey($item)->delete();

        if (! $deleted) {
            return $this->error('Cart item not found.', 404);
        }

        $cart->calculateTotal();

        return $this->success($cart->fresh()->load(['items.product', 'items.variant']));
    }
}
