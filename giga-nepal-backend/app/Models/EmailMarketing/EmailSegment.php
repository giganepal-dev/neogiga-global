<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmailSegment extends Model
{
    protected $table = 'email_segments';

    protected $fillable = [
        'uuid', 'name', 'slug', 'description', 'segment_type',
        'created_by', 'rules', 'exclusions', 'recalc_strategy',
        'last_recalculated_at', 'subscriber_count', 'is_active',
    ];

    protected $casts = [
        'rules' => 'array',
        'exclusions' => 'array',
        'last_recalculated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $segment): void {
            if (empty($segment->uuid)) {
                $segment->uuid = (string) Str::uuid();
            }
            if (empty($segment->slug) && ! empty($segment->name)) {
                $segment->slug = Str::slug($segment->name);
            }
        });
    }
}
