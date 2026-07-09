<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Cart;
use App\Models\Marketplace\CartItem;
use App\Models\Marketplace\CartReservation;
use App\Models\Marketplace\InventoryStock;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceProductPrice;
use App\Models\Marketplace\Product;
use App\Services\Inventory\InventoryReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    use ApiResponses;

    protected InventoryReservationService $reservationService;

    public function __construct(InventoryReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->activeCart($request)->load(['items.product', 'items.variant']);
        
        // Include reservation status and expiry info
        $hasActiveReservations = $this->reservationService->userHasActiveReservations($request->user()->id);
        $reservationSummary = null;
        
        if ($hasActiveReservations) {
            $reservations = CartReservation::query()
                ->whereHas('cart', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                })
                ->where('status', CartReservation::STATUS_ACTIVE)
                ->where('expires_at', '>', now())
                ->orderBy('expires_at')
                ->first();
            
            if ($reservations) {
                $reservationSummary = [
                    'has_active_reservations' => true,
                    'expires_at' => $reservations->expires_at,
                    'remaining_seconds' => $reservations->remainingSeconds(),
                ];
            }
        }
        
        return $this->success([
            'cart' => $cart,
            'reservation' => $reservationSummary,
        ]);
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

        if (!$product) {
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
            if (!$this->hasStock($validated['product_id'], $marketplaceId, $newQuantity, $variantId)) {
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

            // Create inventory reservations (15-minute soft reserve)
            $reservationResult = $this->reservationService->reserveCartInventory($cart);

            $responseData = [
                'cart' => $cart->fresh()->load(['items.product', 'items.variant']),
            ];

            if ($reservationResult['success']) {
                $responseData['reservation'] = [
                    'reserved' => true,
                    'expires_at' => $reservationResult['expires_at'],
                    'message' => 'Inventory reserved for 15 minutes. Complete checkout before expiry.',
                ];
            } else {
                $responseData['reservation'] = [
                    'reserved' => false,
                    'failed_items' => $reservationResult['failed_items'] ?? [],
                ];
            }

            return $this->success($responseData, 201);
        });
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

        $cart = $this->activeCart($request);
        $cartItem = $cart->items()->whereKey($item)->first();

        if (!$cartItem) {
            return $this->error('Cart item not found.', 404);
        }

        if (!$this->hasStock($cartItem->product_id, $cart->marketplace_id, $validated['quantity'], $cartItem->variant_id)) {
            return $this->error('Requested quantity is not available in regional inventory.', 422);
        }

        return DB::transaction(function () use ($cart, $cartItem, $validated) {
            // Release existing reservations for this item before updating
            $this->reservationService->releaseCartReservations(
                $cart, 
                'Cart item quantity updated - will re-reserve'
            );

            $cartItem->forceFill(['quantity' => $validated['quantity']])->save();
            $cart->calculateTotal();

            // Re-reserve with new quantities
            $reservationResult = $this->reservationService->reserveCartInventory($cart);

            $responseData = [
                'cart' => $cart->fresh()->load(['items.product', 'items.variant']),
            ];

            if ($reservationResult['success']) {
                $responseData['reservation'] = [
                    'reserved' => true,
                    'expires_at' => $reservationResult['expires_at'],
                    'message' => 'Inventory re-reserved for 15 minutes',
                ];
            }

            return $this->success($responseData);
        });
    }

    public function removeItem(Request $request, int $item): JsonResponse
    {
        $cart = $this->activeCart($request);
        $deleted = $cart->items()->whereKey($item)->delete();

        if (!$deleted) {
            return $this->error('Cart item not found.', 404);
        }

        return DB::transaction(function () use ($cart) {
            // Release reservations for removed item
            $this->reservationService->releaseCartReservations(
                $cart,
                'Cart item removed'
            );

            $cart->calculateTotal();

            return $this->success($cart->fresh()->load(['items.product', 'items.variant']));
        });
    }

    /**
     * Clear entire cart and release all reservations
     */
    public function clear(Request $request): JsonResponse
    {
        $cart = $this->activeCart($request);

        return DB::transaction(function () use ($cart) {
            // Release all reservations
            $this->reservationService->releaseCartReservations(
                $cart,
                'Cart cleared by user'
            );

            // Delete all cart items
            $cart->items()->delete();

            // Reset cart totals
            $cart->update([
                'subtotal' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'shipping_total' => 0,
                'grand_total' => 0,
                'item_count' => 0,
            ]);

            return $this->success($cart, 'Cart cleared successfully');
        });
    }

    /**
     * Get reservation status for current cart
     */
    public function reservationStatus(Request $request): JsonResponse
    {
        $cart = $this->activeCart($request);
        
        $reservations = CartReservation::query()
            ->where('cart_id', $cart->id)
            ->where('status', CartReservation::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->get();

        if ($reservations->isEmpty()) {
            return $this->success([
                'has_active_reservations' => false,
                'message' => 'No active inventory reservations',
            ]);
        }

        $earliestExpiry = $reservations->min('expires_at');
        $totalReserved = $reservations->sum('quantity_reserved');

        return $this->success([
            'has_active_reservations' => true,
            'reservation_count' => $reservations->count(),
            'total_units_reserved' => $totalReserved,
            'expires_at' => $earliestExpiry,
            'remaining_seconds' => max(0, now()->diffInSeconds($earliestExpiry)),
            'remaining_minutes' => max(0, floor(now()->diffInSeconds($earliestExpiry) / 60)),
            'message' => 'Complete checkout before reservation expires',
        ]);
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
