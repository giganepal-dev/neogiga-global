<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class EmailSegment extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'is_dynamic',
        'conditions',
        'subscriber_count',
        'last_calculated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_dynamic' => 'boolean',
        'conditions' => 'array',
        'subscriber_count' => 'integer',
        'last_calculated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EmailSegment $segment) {
            if (empty($segment->uuid)) {
                $segment->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(EmailCampaign::class, 'email_campaign_segments')
            ->withPivot('relation_type')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_dynamic', true);
    }
}
