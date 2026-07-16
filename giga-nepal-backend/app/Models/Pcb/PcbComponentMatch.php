<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PcbComponentMatch extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Str::uuid();
            }
        });
    }

    protected $fillable = [
        'project_id', 'bom_line_id',
        'requested_mpn', 'requested_manufacturer',
        'requested_description', 'requested_package',
        'matched_product_id', 'matched_mpn', 'matched_manufacturer',
        'match_confidence', 'match_reason',
        'customer_approved', 'approved_by_id', 'approved_at',
        'engineer_approved', 'engineer_approved_by_id', 'engineer_approved_at',
        'alternative_allowed', 'alternative_candidates',
    ];

    protected $casts = [
        'customer_approved' => 'boolean',
        'approved_at' => 'datetime',
        'engineer_approved' => 'boolean',
        'engineer_approved_at' => 'datetime',
        'alternative_allowed' => 'boolean',
        'alternative_candidates' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class, 'project_id');
    }

    public function matchedProduct(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'matched_product_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'approved_by_id');
    }

    public function engineerApprovedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'engineer_approved_by_id');
    }

    public function substitutions(): HasMany
    {
        return $this->hasMany(PcbComponentSubstitution::class);
    }
}
