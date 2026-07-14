<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class EmailCampaignService
{
    public function schedule(int $id, ?string $when = null): void
    {
        DB::table('email_campaigns')->where('id', $id)->update(['status' => 'scheduled', 'scheduled_at' => $when ?: now(), 'updated_at' => now()]);
    }
}
