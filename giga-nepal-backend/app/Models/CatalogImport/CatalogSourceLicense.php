<?php

namespace App\Models\CatalogImport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * CatalogSourceLicense Model
 * 
 * Tracks licensing terms and restrictions for catalog data sources.
 */
class CatalogSourceLicense extends Model
{
    protected $fillable = [
        'catalog_source_id',
        'license_type',
        'license_key',
        'terms_url',
        'valid_from',
        'valid_until',
        'max_products',
        'max_requests_per_day',
        'allowed_regions',
        'allowed_data_fields',
        'allows_caching',
        'allows_redistribution',
        'attribution_text',
        'status',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'allowed_regions' => 'array',
        'allowed_data_fields' => 'array',
        'allows_caching' => 'boolean',
        'allows_redistribution' => 'boolean',
    ];

    /**
     * License type constants
     */
    const TYPE_COMMERCIAL = 'commercial';
    const TYPE_FREE = 'free';
    const TYPE_TRIAL = 'trial';
    const TYPE_PARTNER = 'partner';
    const TYPE_OEM = 'oem';

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PENDING = 'pending';

    public function source(): BelongsTo
    {
        return $this->belongsTo(CatalogSource::class, 'catalog_source_id');
    }

    /**
     * Check if license is currently active and valid
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Check if a region is allowed under this license
     */
    public function allowsRegion(string $region): bool
    {
        if (empty($this->allowed_regions)) {
            return true; // No restriction means all regions allowed
        }
        return in_array(strtoupper($region), array_map('strtoupper', $this->allowed_regions));
    }

    /**
     * Check if a data field is allowed to be stored
     */
    public function allowsField(string $fieldName): bool
    {
        if (empty($this->allowed_data_fields)) {
            return true; // No restriction means all fields allowed
        }
        return in_array($fieldName, $this->allowed_data_fields);
    }

    /**
     * Get days until license expires (null if no expiry)
     */
    public function daysUntilExpiry(): ?int
    {
        if (!$this->valid_until) {
            return null;
        }
        return now()->diffInDays($this->valid_until, false);
    }

    /**
     * Scope to get only valid licenses
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            });
    }

    /**
     * Scope to get licenses expiring soon
     */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [now(), now()->addDays($days)]);
    }
}
