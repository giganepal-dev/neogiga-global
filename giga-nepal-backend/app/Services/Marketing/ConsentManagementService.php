<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class ConsentManagementService
{
    public function grant(?int $customerId, ?string $email, ?string $phone, string $channel, string $purpose, string $source = 'api'): void
    {
        DB::table('customer_consents')->insert([
            'customer_profile_id' => $customerId, 'email' => $email, 'phone' => $phone, 'channel' => $channel, 'purpose' => $purpose,
            'granted' => true, 'source' => $source, 'granted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function revoke(?int $customerId, ?string $email, ?string $phone, string $channel, string $reason = 'unsubscribe'): void
    {
        DB::table('customer_consents')->insert([
            'customer_profile_id' => $customerId, 'email' => $email, 'phone' => $phone, 'channel' => $channel, 'purpose' => 'marketing',
            'granted' => false, 'source' => $reason, 'revoked_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('suppression_lists')->insert(['channel' => $channel, 'email' => $email, 'phone' => $phone, 'reason' => $reason, 'source' => 'api', 'suppressed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function canSendMarketing(?string $email, ?string $phone, string $channel): bool
    {
        $suppressed = DB::table('suppression_lists')->where('channel', $channel)
            ->when($email, fn ($q) => $q->where('email', $email))
            ->when(!$email && $phone, fn ($q) => $q->where('phone', $phone))->exists();
        if ($suppressed) return false;
        return DB::table('customer_consents')->where('channel', $channel)->where('purpose', 'marketing')->where('granted', true)
            ->when($email, fn ($q) => $q->where('email', $email))
            ->when(!$email && $phone, fn ($q) => $q->where('phone', $phone))->exists();
    }
}
