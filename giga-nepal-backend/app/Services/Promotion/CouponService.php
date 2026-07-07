<?php

namespace App\Services\Promotion;

use App\Models\Promotion\Coupon;
use App\Models\Promotion\CouponRedemption;
use Illuminate\Support\Facades\DB;

/**
 * Server-side coupon validation and redemption. The discount is always
 * computed here from the trusted server subtotal — never from client input.
 */
class CouponService
{
    /**
     * @return array{valid:bool, reason:?string, discount:float, free_shipping:bool, coupon:?Coupon}
     */
    public function validate(string $code, float $subtotal, ?int $userId = null, ?int $marketplaceId = null): array
    {
        $coupon = Coupon::whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])->first();

        if (!$coupon) {
            return $this->fail('Coupon not found.');
        }
        if (!$coupon->isLiveNow()) {
            return $this->fail('Coupon is not active.', $coupon);
        }
        if ($coupon->marketplace_id && $marketplaceId && (int) $coupon->marketplace_id !== $marketplaceId) {
            return $this->fail('Coupon not valid for this marketplace.', $coupon);
        }
        if ($coupon->min_order_total !== null && $subtotal < (float) $coupon->min_order_total) {
            return $this->fail('Order total below coupon minimum.', $coupon);
        }
        if ($coupon->usage_limit !== null && (int) $coupon->used_count >= (int) $coupon->usage_limit) {
            return $this->fail('Coupon usage limit reached.', $coupon);
        }
        if ($coupon->usage_limit_per_user !== null && $userId) {
            $userUses = CouponRedemption::where('coupon_id', $coupon->id)->where('user_id', $userId)->count();
            if ($userUses >= (int) $coupon->usage_limit_per_user) {
                return $this->fail('You have already used this coupon.', $coupon);
            }
        }

        $freeShipping = $coupon->type === 'free_shipping';
        $discount = $this->computeDiscount($coupon, $subtotal);

        return [
            'valid' => true,
            'reason' => null,
            'discount' => $discount,
            'free_shipping' => $freeShipping,
            'coupon' => $coupon,
        ];
    }

    public function computeDiscount(Coupon $coupon, float $subtotal): float
    {
        $discount = match ($coupon->type) {
            'fixed' => (float) $coupon->value,
            'percentage' => round($subtotal * ((float) $coupon->value / 100), 2),
            default => 0.0, // free_shipping applies to shipping, not subtotal
        };

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return max(0.0, min(round($discount, 2), $subtotal));
    }

    /**
     * Consume a coupon for a specific order. Idempotent per (coupon, order)
     * via the DB unique constraint. Called from checkout when wiring lands.
     */
    public function redeem(Coupon $coupon, float $discount, string $currency, ?int $userId, ?int $orderId): CouponRedemption
    {
        return DB::transaction(function () use ($coupon, $discount, $currency, $userId, $orderId) {
            $existing = CouponRedemption::where('coupon_id', $coupon->id)->where('order_id', $orderId)->first();
            if ($existing) {
                return $existing;
            }

            $redemption = CouponRedemption::create([
                'coupon_id' => $coupon->id,
                'user_id' => $userId,
                'order_id' => $orderId,
                'discount_amount' => round($discount, 2),
                'currency' => $currency,
                'redeemed_at' => now(),
            ]);

            $coupon->increment('used_count');

            return $redemption;
        });
    }

    private function fail(string $reason, ?Coupon $coupon = null): array
    {
        return ['valid' => false, 'reason' => $reason, 'discount' => 0.0, 'free_shipping' => false, 'coupon' => $coupon];
    }
}
