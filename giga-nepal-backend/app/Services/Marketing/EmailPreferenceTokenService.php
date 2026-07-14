<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailPreferenceTokenService
{
    public function issue(string $email, ?int $campaignId = null, ?int $listId = null, string $scope = 'all_marketing'): array
    {
        $token = Str::random(64);
        $hash = hash('sha256', $token);
        DB::table('unsubscribes')->insert([
            'email' => mb_strtolower(trim($email)),
            'channel' => 'email',
            'email_campaign_id' => $campaignId,
            'contact_list_id' => $listId,
            'scope' => $scope,
            'token_hash' => $hash,
            'source' => 'message_preference_link',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'token' => $token,
            'unsubscribe_url' => url('/email/unsubscribe/'.$token),
            'preferences_url' => url('/email/preferences/'.$token),
        ];
    }

    public function find(string $token): ?object
    {
        return DB::table('unsubscribes')->where('token_hash', hash('sha256', $token))->first();
    }

    public function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible.str_repeat('•', max(3, mb_strlen($local) - mb_strlen($visible))).'@'.$domain;
    }
}
