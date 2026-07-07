<?php

namespace App\Services\Affiliate;

use App\Models\Affiliate\Affiliate;
use App\Models\Affiliate\CommissionLedgerEntry;
use App\Models\Affiliate\ReferralAttribution;
use App\Models\Affiliate\ReferralCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AffiliateService
{
    public function __construct(private readonly CommissionCalculationService $commissions)
    {
    }

    /** Register an affiliate application (status = pending; admin approves later). */
    public function apply(User $user, array $data): Affiliate
    {
        return DB::transaction(function () use ($user, $data) {
            $affiliate = Affiliate::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $data['display_name'] ?? $user->name,
                    'email' => $data['email'] ?? $user->email,
                    'status' => 'pending',
                    'default_currency' => $data['default_currency'] ?? 'USD',
                    'payout_method' => $data['payout_method'] ?? null,
                    'payout_details' => $data['payout_details'] ?? null,
                ],
            );

            if ($affiliate->codes()->count() === 0) {
                $this->issueCode($affiliate);
            }

            return $affiliate->fresh('codes');
        });
    }

    public function issueCode(Affiliate $affiliate, ?string $landingUrl = null): ReferralCode
    {
        return $affiliate->codes()->create([
            'code' => $this->uniqueCode(),
            'landing_url' => $landingUrl,
            'is_active' => true,
        ]);
    }

    private function uniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (ReferralCode::where('code', $code)->exists());

        return $code;
    }

    /**
     * Record a referral click/visit. Only stores hashed IP/UA (no raw PII).
     * Returns null for unknown/inactive codes (fail-soft, no error to visitor).
     */
    public function trackClick(string $code, array $ctx): ?ReferralAttribution
    {
        $referral = ReferralCode::where('code', $code)->where('is_active', true)->first();
        if (!$referral) {
            return null;
        }

        $referral->increment('click_count');

        return ReferralAttribution::create([
            'referral_code_id' => $referral->id,
            'affiliate_id' => $referral->affiliate_id,
            'visitor_token' => $ctx['visitor_token'] ?? (string) Str::uuid(),
            'utm_source' => $ctx['utm_source'] ?? null,
            'utm_medium' => $ctx['utm_medium'] ?? null,
            'utm_campaign' => $ctx['utm_campaign'] ?? null,
            'source_url' => isset($ctx['source_url']) ? mb_substr($ctx['source_url'], 0, 1024) : null,
            'ip_hash' => isset($ctx['ip']) ? hash('sha256', (string) $ctx['ip']) : null,
            'user_agent_hash' => isset($ctx['user_agent']) ? hash('sha256', (string) $ctx['user_agent']) : null,
            'status' => 'pending',
            'attributed_at' => now(),
        ]);
    }

    /** Bind a pending anonymous attribution to a user once they authenticate. */
    public function attributeUser(string $visitorToken, int $userId): void
    {
        ReferralAttribution::where('visitor_token', $visitorToken)
            ->whereNull('user_id')
            ->where('status', 'pending')
            ->update(['user_id' => $userId]);
    }

    /**
     * On a confirmed order, create a PENDING commission entry.
     * Guards: self-referral blocked, one entry per (order, affiliate), never
     * approved here — approval happens only after the order is paid/delivered.
     *
     * @param object $order  A row/model from the `orders` table (trusted server data).
     */
    public function recordConversion(object $order): ?CommissionLedgerEntry
    {
        $attribution = $this->attributionForOrder($order);
        if (!$attribution || !$attribution->affiliate_id) {
            return null;
        }

        $affiliate = Affiliate::find($attribution->affiliate_id);
        if (!$affiliate || !$affiliate->isApproved()) {
            return null;
        }

        // Self-referral guard.
        if ($affiliate->user_id && $affiliate->user_id === ($order->user_id ?? null)) {
            return null;
        }

        // Idempotency: one commission per (order, affiliate).
        $existing = CommissionLedgerEntry::where('order_id', $order->id)
            ->where('affiliate_id', $affiliate->id)->first();
        if ($existing) {
            return $existing;
        }

        $rule = $this->commissions->resolveRule($affiliate, $order->marketplace_id ?? null);
        if (!$rule) {
            return null;
        }

        $orderTotal = (float) ($order->grand_total ?? 0);
        $amount = $this->commissions->calculate($rule, $orderTotal);
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($affiliate, $order, $attribution, $rule, $orderTotal, $amount) {
            $attribution->update([
                'status' => 'converted',
                'converted_order_id' => $order->id,
                'converted_at' => now(),
            ]);
            optional($attribution->code)->increment('order_count');

            return CommissionLedgerEntry::create([
                'affiliate_id' => $affiliate->id,
                'order_id' => $order->id,
                'referral_attribution_id' => $attribution->id,
                'commission_rule_id' => $rule->id,
                'currency' => $order->currency_code ?? $affiliate->default_currency,
                'order_total_snapshot' => $orderTotal,
                'commission_amount' => $amount,
                'status' => 'pending', // never approved until order paid/delivered
                'country_id' => $affiliate->country_id,
            ]);
        });
    }

    private function attributionForOrder(object $order): ?ReferralAttribution
    {
        $query = ReferralAttribution::query()->where('status', 'pending');

        if (!empty($order->user_id)) {
            return $query->where('user_id', $order->user_id)->latest('id')->first();
        }

        return null;
    }
}
