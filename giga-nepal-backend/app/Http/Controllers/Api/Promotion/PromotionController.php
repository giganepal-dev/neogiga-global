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
}
