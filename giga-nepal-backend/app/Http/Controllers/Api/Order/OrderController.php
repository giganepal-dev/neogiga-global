<?php

namespace App\Http\Controllers\Api\Order;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Cart;
use App\Models\Marketplace\Marketplace;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Marketplace\Cart as CartModel;
use App\Services\Affiliate\AffiliateService;
use App\Services\Promotion\CouponService;
use App\Services\Promotion\GiftCardService;
use App\Services\Marketplace\ProductAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly AffiliateService $affiliates,
        private readonly CouponService $coupons,
        private readonly GiftCardService $giftCards,
        private readonly ProductAvailabilityService $availability,
    ) {
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirm' => ['accepted'],
            'payment_method' => ['required', 'in:bank_transfer,cod,manual'],
            'billing_address' => ['sometimes', 'array'],
            'shipping_address' => ['sometimes', 'array'],
            'customer_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $cart = Cart::query()
            ->active()
            ->where('user_id', $request->user()->id)
            ->with(['items.product'])
            ->latest()
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return $this->error('Active cart is empty.', 422);
        }

        foreach ($cart->items as $item) {
            $availability = $this->availability->forProduct($item->product_id, Marketplace::find($cart->marketplace_id), $item->variant_id);
            if ((int) $availability['available_stock'] < (int) $item->quantity || ($availability['quote_only'] ?? false)) {
                return $this->error("Insufficient stock for {$item->product?->name}.", 422);
            }
            if (($availability['price']['selling_price'] ?? 0) <= 0 || round((float) $availability['price']['selling_price'], 4) !== round((float) $item->unit_price, 4)) {
                return $this->error("Price changed for {$item->product?->name}; refresh the cart before checkout.", 422);
            }
        }

        return DB::transaction(function () use ($cart, $request, $validated) {
            $cart->calculateTotal();
            $cart->refresh()->load(['items.product']);

            // Server-side promotions (re-validated from cart metadata; never trusted from client).
            $promo = $this->resolvePromotions($cart, $request->user()->id);
            $orderDiscount = round((float) $cart->discount_total + $promo['coupon_discount'], 2);
            $orderGrand = max(0.0, round((float) $cart->grand_total - $promo['coupon_discount'], 2));

            $order = Order::create([
                'order_number' => 'NG-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                'user_id' => $request->user()->id,
                'marketplace_id' => $cart->marketplace_id,
                'status' => 'pending',
                'currency_code' => $cart->currency_code,
                'subtotal' => $cart->subtotal,
                'tax_total' => $cart->tax_total,
                'discount_total' => $orderDiscount,
                'shipping_total' => $cart->shipping_total,
                'grand_total' => $orderGrand,
                'amount_paid' => 0,
                'amount_due' => $orderGrand,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'billing_address' => $validated['billing_address'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'customer_notes' => $validated['customer_notes'] ?? null,
                'metadata' => [
                    'cart_id' => $cart->id,
                    'phase' => 'phase_1_pending_payment',
                ],
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->variant_id,
                    'product_name' => $item->product?->name ?? 'Unknown product',
                    'product_sku' => $item->product?->sku ?? 'UNKNOWN',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_amount' => $item->tax_amount,
                    'discount_amount' => $item->discount_amount,
                    'total_price' => $item->total,
                    'metadata' => $item->metadata,
                ]);
            }

            // Redeem coupon (records redemption + increments usage; server-side amount).
            if ($promo['coupon'] && $promo['coupon_discount'] > 0) {
                try {
                    $this->coupons->redeem($promo['coupon'], $promo['coupon_discount'], $order->currency_code, $request->user()->id, $order->id);
                } catch (\Throwable) {
                    // non-critical: coupon logging failure does not affect the order
                }
            }

            // Redeem gift card up to the amount due (row-locked, server-side).
            $amountDue = (float) $order->grand_total;
            if (!empty($promo['gift_card_code'])) {
                try {
                    $gc = $this->giftCards->redeem($promo['gift_card_code'], $amountDue, $request->user()->id, $order->id);
                    if (($gc['applied'] ?? 0) > 0) {
                        Payment::create([
                            'payment_number' => 'PAY-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                            'order_id' => $order->id,
                            'marketplace_id' => $order->marketplace_id,
                            'payment_method' => 'gift_card',
                            'payment_gateway' => 'wallet',
                            'amount' => $gc['applied'],
                            'currency_code' => $order->currency_code,
                            'status' => 'captured',
                            'paid_at' => now(),
                            'payment_details' => ['gift_card' => true],
                        ]);
                        $amountDue = max(0.0, round($amountDue - (float) $gc['applied'], 2));
                        $order->forceFill([
                            'amount_paid' => $gc['applied'],
                            'amount_due' => $amountDue,
                            'payment_status' => $amountDue <= 0 ? 'paid' : 'pending',
                        ])->save();
                    }
                } catch (\Throwable) {
                    // gift card not redeemable — proceed without it
                }
            }

            // Remaining balance due -> pending payment via the chosen method.
            if ($amountDue > 0) {
                Payment::create([
                    'payment_number' => 'PAY-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                    'order_id' => $order->id,
                    'marketplace_id' => $order->marketplace_id,
                    'payment_method' => $validated['payment_method'],
                    'payment_gateway' => 'manual',
                    'amount' => $amountDue,
                    'currency_code' => $order->currency_code,
                    'status' => 'pending',
                    'payment_details' => [
                        'requires_manual_confirmation' => true,
                        'provider_call_made' => false,
                    ],
                ]);
            }

            $cart->forceFill(['is_active' => false])->save();

            // Affiliate: record a PENDING commission if this order was referred.
            // Guarded — referral attribution must never break checkout.
            try {
                $this->affiliates->recordConversion($order);
            } catch (\Throwable) {
                // non-critical: referral tracking failure does not affect the order
            }

            return $this->success($order->fresh()->load(['items', 'payments']), 201);
        });
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(25);

        return $this->success($orders);
    }

    public function show(Request $request, int $order): JsonResponse
    {
        $orderModel = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items', 'payments'])
            ->find($order);

        if (!$orderModel) {
            return $this->error('Order not found.', 404);
        }

        return $this->success($orderModel);
    }

    public function invoice(int $order): JsonResponse
    {
        return $this->notImplemented('Invoice generation', 'Phase 2');
    }

    /**
     * Resolve server-side promotions from the cart metadata. The coupon is
     * re-validated (never trusting a stored discount); the gift-card code is
     * carried through for row-locked redemption after the order is created.
     *
     * @return array{coupon:?\App\Models\Promotion\Coupon, coupon_discount:float, gift_card_code:?string}
     */
    private function resolvePromotions(CartModel $cart, int $userId): array
    {
        $meta = $cart->metadata ?? [];

        $couponDiscount = 0.0;
        $coupon = null;
        if (!empty($meta['coupon_code'])) {
            $res = $this->coupons->validate($meta['coupon_code'], (float) $cart->subtotal, $userId, $cart->marketplace_id);
            if ($res['valid']) {
                $couponDiscount = (float) $res['discount'];
                $coupon = $res['coupon'];
            }
        }

        return [
            'coupon' => $coupon,
            'coupon_discount' => $couponDiscount,
            'gift_card_code' => $meta['gift_card_code'] ?? null,
        ];
    }

}
