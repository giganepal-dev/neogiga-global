<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class EmailSuppressionService
{
    public function __construct(private EmailEligibilityService $eligibility) {}

    public function suppress(string $email, string $reason, string $scope = 'marketing', array $context = []): int
    {
        $email = mb_strtolower(trim($email));
        $hardReasons = ['hard_bounce', 'complaint', 'invalid_address', 'blocked', 'provider_suppression', 'security_hold'];
        $existing = DB::table('suppression_lists')->where('channel', 'email')->whereRaw('LOWER(email) = ?', [$email])->where('reason_code', $reason)->where('is_active', true)->first();
        $values = [
            'channel' => 'email',
            'email' => $email,
            'reason' => $reason,
            'reason_code' => $reason,
            'source' => $context['source'] ?? 'system',
            'provider' => $context['provider'] ?? null,
            'provider_reference' => $context['provider_reference'] ?? null,
            'email_webhook_event_id' => $context['email_webhook_event_id'] ?? null,
            'message_scope' => in_array($reason, $hardReasons, true) ? 'global' : $scope,
            'is_global' => in_array($reason, $hardReasons, true),
            'is_hard' => in_array($reason, $hardReasons, true),
            'is_active' => true,
            'suppressed_at' => now(),
            'updated_at' => now(),
        ];
        if ($existing) {
            DB::table('suppression_lists')->where('id', $existing->id)->update($values);

            return $existing->id;
        }

        return DB::table('suppression_lists')->insertGetId($values + ['created_at' => now()]);
    }

    public function marketingDecision(string $email, bool $profileOptIn = false): array
    {
        return $this->eligibility->marketing($email, $profileOptIn);
    }

    public function transactionalDecision(string $email): array
    {
        return $this->eligibility->transactional($email);
    }
}
