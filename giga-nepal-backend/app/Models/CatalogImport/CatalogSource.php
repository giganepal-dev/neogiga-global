<?php

namespace App\Models\CatalogImport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CatalogSource Model
 * 
 * Represents an external data source for catalog imports.
 * Supports API, CSV, XML, JSON, SFTP, and manual sources.
 */
class CatalogSource extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'source_type',
        'provider_name',
        'base_url',
        'documentation_url',
        'authentication_type',
        'country',
        'default_currency',
        'active',
        'priority',
        'rate_limit_per_minute',
        'allowed_data_types',
        'license_notes',
        'attribution_required',
        'last_success_at',
        'last_failure_at',
        'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'attribution_required' => 'boolean',
        'allowed_data_types' => 'array',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'rate_limit_per_minute' => 'integer',
    ];

    /**
     * Source type constants
     */
    const TYPE_API = 'api';
    const TYPE_CSV = 'csv';
    const TYPE_XML = 'xml';
    const TYPE_JSON = 'json';
    const TYPE_SFTP = 'sftp';
    const TYPE_MANUAL = 'manual';

    /**
     * Authentication type constants
     */
    const AUTH_NONE = 'none';
    const AUTH_API_KEY = 'api_key';
    const AUTH_OAUTH2 = 'oauth2';
    const AUTH_BASIC = 'basic';
    const AUTH_TOKEN = 'token';
    const AUTH_CERTIFICATE = 'certificate';

    public function credentials(): HasMany
    {
        return $this->hasMany(CatalogSourceCredential::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(CatalogSourceLicense::class);
    }

    public function fieldMaps(): HasMany
    {
        return $this->hasMany(CatalogSourceFieldMap::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(CatalogSourceRun::class);
    }

    public function rateLimits(): HasMany
    {
        return $this->hasMany(CatalogSourceRateLimit::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(CatalogSourceWebhook::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function latestRun(): HasOne
    {
        return $this->hasOne(CatalogSourceRun::class)->latest();
    }

    public function activeLicense(): HasOne
    {
        return $this->hasOne(CatalogSourceLicense::class)
            ->where('status', 'active')
            ->orderByDesc('valid_until');
    }

    /**
     * Check if source supports a specific data type
     */
    public function supportsDataType(string $dataType): bool
    {
        if (empty($this->allowed_data_types)) {
            return true; // If not specified, assume all supported
        }
        return in_array($dataType, $this->allowed_data_types);
    }

    /**
     * Get the next scheduled reset time for rate limiting
     */
    public function getNextRateLimitReset(): ?\DateTime
    {
        $globalLimit = $this->rateLimits()->whereNull('endpoint_pattern')->first();
        if (!$globalLimit) {
            return null;
        }

        if ($globalLimit->minute_reset_at && $globalLimit->minute_reset_at->isFuture()) {
            return $globalLimit->minute_reset_at;
        }
        if ($globalLimit->hour_reset_at && $globalLimit->hour_reset_at->isFuture()) {
            return $globalLimit->hour_reset_at;
        }
        if ($globalLimit->day_reset_at && $globalLimit->day_reset_at->isFuture()) {
            return $globalLimit->day_reset_at;
        }

        return null;
    }

    /**
     * Record a successful import run
     */
    public function recordSuccess(): void
    {
        $this->update(['last_success_at' => now()]);
    }

    /**
     * Record a failed import run
     */
    public function recordFailure(): void
    {
        $this->update(['last_failure_at' => now()]);
    }
}
