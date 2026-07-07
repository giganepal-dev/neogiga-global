<?php

namespace App\Http\Controllers\Api\Promotion;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Cart;
use App\Services\Promotion\CouponService;
use App\Services\Promotion\GiftCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer-facing promotion checks (auth: api.token). These VALIDATE only —
 * they never mutate the cart or consume the coupon/gift card. The subtotal is
 * always taken from the server-side active cart, never from the request body.
 */
class PromotionController extends Controller
{
    public function __construct(
        private readonly CouponService $coupons,
        private readonly GiftCardService $giftCards,
    ) {
    }

    /** POST /api/v1/coupons/validate */
    public function validateCoupon(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:60']]);

        $cart = Cart::query()->active()->where('user_id', $request->user()->id)->latest()->first();
        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'No active cart.'], 422);
        }

        $result = $this->coupons->validate(
            $data['code'],
            (float) $cart->subtotal,
            $request->user()->id,
            $cart->marketplace_id,
        );

        return response()->json([
            'success' => $result['valid'],
            'data' => [
                'valid' => $result['valid'],
                'reason' => $result['reason'],
                'discount' => $result['discount'],
                'free_shipping' => $result['free_shipping'],
                'cart_subtotal' => (float) $cart->subtotal,
                'currency' => $cart->currency_code,
            ],
        ], $result['valid'] ? 200 : 422);
    }

    /** POST /api/v1/gift-cards/check */
    public function checkGiftCard(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:60']]);

        $result = $this->giftCards->check($data['code']);

        return response()->json(['success' => $result['found'], 'data' => $result], $result['found'] ? 200 : 404);
    }

    /** POST /api/v1/cart/apply-coupon — validate + attach a coupon code to the active cart. */
    public function applyCoupon(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:60']]);

        $cart = $this->activeCart($request);
        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'No active cart.'], 422);
        }

        $result = $this->coupons->validate($data['code'], (float) $cart->subtotal, $request->user()->id, $cart->marketplace_id);
        if (!$result['valid']) {
            return response()->json(['success' => false, 'message' => $result['reason']], 422);
        }

        $meta = $cart->metadata ?? [];
        $meta['coupon_code'] = $result['coupon']->code;
        $cart->update(['metadata' => $meta]);

        return response()->json(['success' => true, 'data' => [
            'coupon_code' => $result['coupon']->code,
            'discount' => $result['discount'],
            'free_shipping' => $result['free_shipping'],
        ]]);
    }

    /** DELETE /api/v1/cart/coupon — detach the coupon from the active cart. */
    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->activeCart($request);
        if ($cart) {
            $meta = $cart->metadata ?? [];
            unset($meta['coupon_code']);
            $cart->update(['metadata' => $meta]);
        }

        return response()->json(['success' => true, 'data' => ['coupon_code' => null]]);
    }

    /** POST /api/v1/cart/apply-gift-card — attach a spendable gift card to the active cart (redeemed at checkout). */
    public function applyGiftCard(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:60']]);

        $cart = $this->activeCart($request);
        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'No active cart.'], 422);
        }

        $check = $this->giftCards->check($data['code']);
        if (!$check['found'] || !$check['spendable']) {
            return response()->json(['success' => false, 'message' => 'Gift card is not usable.'], 422);
        }

        $meta = $cart->metadata ?? [];
        $meta['gift_card_code'] = $data['code'];
        $cart->update(['metadata' => $meta]);

        return response()->json(['success' => true, 'data' => ['balance' => $check['balance'], 'currency' => $check['currency']]]);
    }

    private function activeCart(Request $request): ?Cart
    {
        return Cart::query()->active()->where('user_id', $request->user()->id)->latest()->first();
    }
}
