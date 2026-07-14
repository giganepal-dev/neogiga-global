<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSuppression extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_address',
        'suppression_type', // hard_bounce, spam_complaint, invalid, manual_block, unsubscribed_marketing
        'reason',
        'source', // provider, manual, webhook
        'provider_name',
        'is_active',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function isSuppressedForMarketing(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->suppression_type === 'unsubscribed_marketing') {
            return true;
        }

        // Hard bounces and spam complaints are permanent unless manually cleared
        if (in_array($this->suppression_type, ['hard_bounce', 'spam_complaint']) && !$this->expires_at) {
            return true;
        }

        return $this->expires_at && $this->expires_at->isFuture();
    }
}
