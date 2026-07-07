<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion\Coupon;
use App\Models\Promotion\CouponRedemption;
use App\Models\Promotion\GiftCard;
use App\Services\Promotion\GiftCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Coupon + gift-card administration (admin.token gated by routes).
 */
class PromotionAdminController extends Controller
{
    public function __construct(private readonly GiftCardService $giftCards)
    {
    }

    // ---- Coupons ------------------------------------------------------------

    public function coupons(Request $request): JsonResponse
    {
        $q = Coupon::query();
        if ($request->boolean('active_only')) {
            $q->where('is_active', true);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('id')->paginate(30)]);
    }

    public function storeCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:60', 'unique:coupons,code'],
            'type' => ['required', 'in:percentage,fixed,free_shipping'],
            'value' => ['required_unless:type,free_shipping', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'scope' => ['nullable', 'in:cart,product,category'],
            'applies_to' => ['nullable', 'array'],
            'min_order_total' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'marketplace_id' => ['nullable', 'integer'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if (($data['type'] ?? null) === 'percentage' && ($data['value'] ?? 0) > 100) {
            return response()->json(['success' => false, 'message' => 'Percentage cannot exceed 100.'], 422);
        }

        $data['code'] = strtoupper($data['code'] ?? 'CPN-' . Str::random(7));
        $data['value'] = $data['value'] ?? 0;
        $data['scope'] = $data['scope'] ?? 'cart';

        return response()->json(['success' => true, 'data' => Coupon::create($data)], 201);
    }

    public function updateCoupon(Request $request, Coupon $coupon): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'ends_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $coupon->update($data);

        return response()->json(['success' => true, 'data' => $coupon->fresh()]);
    }

    public function couponRedemptions(Coupon $coupon): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => CouponRedemption::where('coupon_id', $coupon->id)->orderByDesc('id')->paginate(50),
        ]);
    }

    // ---- Gift cards ---------------------------------------------------------

    public function giftCards(Request $request): JsonResponse
    {
        $q = GiftCard::query();
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('id')->paginate(30)]);
    }

    public function storeGiftCard(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'issued_to_email' => ['nullable', 'email', 'max:190'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $card = $this->giftCards->issue(
            (float) $data['amount'],
            $data['currency'] ?? 'USD',
            $data['issued_to_email'] ?? null,
            isset($data['expires_at']) ? new \DateTimeImmutable($data['expires_at']) : null,
        );

        return response()->json(['success' => true, 'data' => $card], 201);
    }

    public function disableGiftCard(GiftCard $giftCard): JsonResponse
    {
        $giftCard->update(['status' => 'disabled']);

        return response()->json(['success' => true, 'data' => ['status' => $giftCard->status]]);
    }

    public function giftCardTransactions(GiftCard $giftCard): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $giftCard->transactions()->orderByDesc('id')->paginate(50),
        ]);
    }
}
