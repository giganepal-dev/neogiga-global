<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class EmailEligibilityService
{
    public function marketing(string $email, bool $profileOptIn = false): array
    {
        $email = mb_strtolower(trim($email));
        $reasons = $this->addressAndSuppressionReasons($email, 'marketing');
        if (DB::table('unsubscribes')->whereRaw('LOWER(email) = ?', [$email])->where('channel', 'email')->whereNotNull('unsubscribed_at')->exists()) {
            $reasons[] = 'unsubscribed';
        }
        if (DB::table('email_preferences')->whereRaw('LOWER(email) = ?', [$email])->where('all_marketing_opt_out', true)->exists()) {
            $reasons[] = 'all_marketing_opt_out';
        }
        $consent = DB::table('customer_consents')->whereRaw('LOWER(email) = ?', [$email])
            ->where('channel', 'email')->where('purpose', 'marketing')->orderByDesc('id')->first();
        $subscription = DB::table('email_subscriptions')->whereRaw('LOWER(email) = ?', [$email])
            ->whereIn('status', ['subscribed', 'confirmed'])->exists();
        $explicit = $consent && (bool) $consent->granted && $consent->status === 'opted_in';
        if (! $profileOptIn && ! $explicit && ! $subscription) {
            $reasons[] = 'no_explicit_marketing_eligibility';
        }

        return ['allowed' => $reasons === [], 'message_class' => 'marketing', 'email_hash' => hash('sha256', $email), 'reasons' => array_values(array_unique($reasons))];
    }

    public function transactional(string $email): array
    {
        $email = mb_strtolower(trim($email));
        $reasons = $this->addressAndSuppressionReasons($email, 'transactional');
        $profile = DB::table('customer_profiles')->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($profile && isset($profile->transactional_eligible) && ! $profile->transactional_eligible) {
            $reasons[] = 'transactional_eligibility_disabled';
        }

        return ['allowed' => $reasons === [], 'message_class' => 'transactional', 'email_hash' => hash('sha256', $email), 'reasons' => array_values(array_unique($reasons))];
    }

    private function addressAndSuppressionReasons(string $email, string $messageClass): array
    {
        $reasons = [];
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['invalid_address'];
        }
        $address = DB::table('contact_email_addresses')->where('normalized_email', $email)->first();
        if ($address && (! $address->is_valid || in_array($address->status, ['invalid', 'hard_bounced', 'complained', 'blocked'], true))) {
            $reasons[] = $address->status ?: 'invalid_address';
        }
        $suppressions = DB::table('suppression_lists')->whereRaw('LOWER(email) = ?', [$email])
            ->where('channel', 'email')->where('is_active', true)
            ->where(function ($expiry) {
                $expiry->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->get();
        foreach ($suppressions as $suppression) {
            $reason = $suppression->reason_code ?: $suppression->reason ?: 'suppressed';
            if ($messageClass === 'marketing') {
                $reasons[] = $reason;

                continue;
            }
            if ($suppression->is_global || $suppression->is_hard || $suppression->message_scope === 'global' || in_array($reason, [
                'hard_bounce', 'complaint', 'invalid_address', 'blocked', 'provider_suppression', 'security_hold',
            ], true)) {
                $reasons[] = $reason;
            }
        }

        return $reasons;
    }
}
