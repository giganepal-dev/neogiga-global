<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class ConsentManagementService
{
    public function __construct(private EmailSuppressionService $suppressions) {}

    public function grant(?int $customerId, ?string $email, ?string $phone, string $channel, string $purpose, string $source = 'api'): void
    {
        DB::table('customer_consents')->insert([
            'customer_profile_id' => $customerId, 'email' => $email, 'phone' => $phone, 'channel' => $channel, 'purpose' => $purpose,
            'granted' => true, 'status' => 'opted_in', 'source' => $source, 'granted_at' => now(), 'effective_at' => now(), 'recorded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function revoke(?int $customerId, ?string $email, ?string $phone, string $channel, string $reason = 'unsubscribe'): void
    {
        DB::table('customer_consents')->insert([
            'customer_profile_id' => $customerId, 'email' => $email, 'phone' => $phone, 'channel' => $channel, 'purpose' => 'marketing',
            'granted' => false, 'status' => $reason === 'unsubscribe' ? 'unsubscribed' : 'opted_out', 'source' => $reason, 'revoked_at' => now(), 'effective_at' => now(), 'recorded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($channel === 'email' && $email) {
            $this->suppressions->suppress($email, $reason === 'unsubscribe' ? 'unsubscribed' : $reason, 'marketing', ['source' => 'api']);
        } else {
            DB::table('suppression_lists')->insert(['channel' => $channel, 'email' => $email, 'phone' => $phone, 'reason' => $reason, 'reason_code' => $reason, 'message_scope' => 'marketing', 'is_active' => true, 'source' => 'api', 'suppressed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function canSendMarketing(?string $email, ?string $phone, string $channel): bool
    {
        if ($channel === 'email' && $email) {
            return $this->suppressions->marketingDecision($email)['allowed'];
        }
        $suppressed = DB::table('suppression_lists')->where('channel', $channel)->where('is_active', true)
            ->when($email, fn ($q) => $q->where('email', $email))->when(! $email && $phone, fn ($q) => $q->where('phone', $phone))->exists();
        if ($suppressed) {
            return false;
        }

        return DB::table('customer_consents')->where('channel', $channel)->where('purpose', 'marketing')->where('granted', true)
            ->when($email, fn ($q) => $q->where('email', $email))
            ->when(! $email && $phone, fn ($q) => $q->where('phone', $phone))->exists();
    }
}
