<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class MarketingModel extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array', 'rules' => 'array', 'targeting_rules' => 'array', 'settings' => 'array', 'value' => 'array',
            'interests' => 'array', 'subscribed_categories' => 'array', 'variables' => 'array', 'conditions' => 'array', 'context' => 'array',
            'marketing_opt_in' => 'boolean', 'whatsapp_opt_in' => 'boolean', 'is_active' => 'boolean', 'is_enabled' => 'boolean', 'test_mode' => 'boolean',
            'confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime', 'scheduled_at' => 'datetime', 'sent_at' => 'datetime', 'occurred_at' => 'datetime',
        ];
    }
}
