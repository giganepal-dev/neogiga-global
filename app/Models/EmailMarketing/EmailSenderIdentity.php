<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class EmailSenderIdentity extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'sender_email',
        'sender_name',
        'reply_to_email',
        'provider',
        'verification_status',
        'verified_at',
        'region_id',
        'country_code',
        'is_default',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EmailSenderIdentity $identity) {
            if (empty($identity->uuid)) {
                $identity->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    public function region()
    {
        return $this->belongsTo(\App\Models\Marketplace\Region::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(EmailGroup::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }
}
