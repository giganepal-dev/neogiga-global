<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class RegionalEmailBrandingService
{
    public function senderProfile(?int $marketplaceId, string $purpose): ?object
    {
        $query = DB::table('email_sender_profiles')->where('purpose', $purpose);
        $profile = $marketplaceId ? (clone $query)->where('marketplace_id', $marketplaceId)->first() : null;
        if ($profile) {
            return $profile;
        }

        // Marketplaces without their own profile fall back to the verified
        // Global sender — a regional email must never hard-fail on branding.
        return (clone $query)->where('name', 'like', 'NeoGiga Global%')->first();
    }

    public function context(?int $marketplaceId, string $purpose = 'transactional'): array
    {
        $profile = $this->senderProfile($marketplaceId, $purpose);
        $code = $marketplaceId ? mb_strtolower((string) DB::table('marketplaces')->where('id', $marketplaceId)->value('code')) : 'global';
        $region = config('marketing.regional.'.$code, config('marketing.regional.global', []));

        return [
            'sender_profile_id' => $profile->id ?? null,
            'marketplace_name' => $profile->from_name ?? ($region['name'] ?? 'NeoGiga Global'),
            'base_url' => $profile->base_url ?? ($region['base_url'] ?? 'https://neogiga.com'),
            'currency' => $profile->default_currency ?? ($region['currency'] ?? 'USD'),
            'language' => $profile->default_language ?? 'en',
            'from_name' => $profile->from_name ?? null,
            'from_email' => $profile->from_email ?? null,
            'reply_to' => $profile->reply_to ?? null,
            'verified' => (bool) ($profile->is_verified ?? false),
            'enabled' => (bool) ($profile->is_enabled ?? false),
        ];
    }
}
