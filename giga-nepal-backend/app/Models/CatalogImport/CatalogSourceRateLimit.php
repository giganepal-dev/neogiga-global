<?php

namespace App\Models\CatalogImport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CatalogSourceRateLimit Model
 * 
 * Tracks API rate limit usage per source and endpoint.
 */
class CatalogSourceRateLimit extends Model
{
    protected $fillable = [
        'catalog_source_id',
        'endpoint_pattern',
        'limit_per_minute',
        'limit_per_hour',
        'limit_per_day',
        'current_minute_count',
        'current_hour_count',
        'current_day_count',
        'minute_reset_at',
        'hour_reset_at',
        'day_reset_at',
        'is_throttled',
        'throttle_until',
    ];

    protected $casts = [
        'is_throttled' => 'boolean',
        'throttle_until' => 'datetime',
        'minute_reset_at' => 'datetime',
        'hour_reset_at' => 'datetime',
        'day_reset_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(CatalogSource::class, 'catalog_source_id');
    }

    /**
     * Check if a request can be made without exceeding limits
     */
    public function canMakeRequest(): bool
    {
        if ($this->is_throttled && $this->throttle_until?->isFuture()) {
            return false;
        }

        $now = now();

        // Reset counters if past reset time
        $this->resetCountersIfNeeded($now);

        // Check limits in order of granularity
        if ($this->limit_per_minute && $this->current_minute_count >= $this->limit_per_minute) {
            return false;
        }

        if ($this->limit_per_hour && $this->current_hour_count >= $this->limit_per_hour) {
            return false;
        }

        if ($this->limit_per_day && $this->current_day_count >= $this->limit_per_day) {
            return false;
        }

        return true;
    }

    /**
     * Record a request was made
     */
    public function recordRequest(): void
    {
        $this->resetCountersIfNeeded(now());

        $this->increment('current_minute_count');
        $this->increment('current_hour_count');
        $this->increment('current_day_count');

        $this->checkThrottleStatus();
    }

    /**
     * Reset counters if past their reset times
     */
    protected function resetCountersIfNeeded(\DateTime $now): void
    {
        $updated = false;

        if ($this->minute_reset_at && $now >= $this->minute_reset_at) {
            $this->current_minute_count = 0;
            $this->minute_reset_at = $now->copy()->addMinute();
            $updated = true;
        }

        if ($this->hour_reset_at && $now >= $this->hour_reset_at) {
            $this->current_hour_count = 0;
            $this->hour_reset_at = $now->copy()->addHour();
            $updated = true;
        }

        if ($this->day_reset_at && $now >= $this->day_reset_at) {
            $this->current_day_count = 0;
            $this->day_reset_at = $now->copy()->addDay();
            $updated = true;
        }

        if ($updated) {
            $this->save();
        }
    }

    /**
     * Check and update throttle status
     */
    protected function checkThrottleStatus(): void
    {
        $shouldThrottle = false;
        $throttleUntil = null;

        if ($this->limit_per_minute && $this->current_minute_count >= $this->limit_per_minute) {
            $shouldThrottle = true;
            $throttleUntil = now()->addMinute();
        } elseif ($this->limit_per_hour && $this->current_hour_count >= $this->limit_per_hour) {
            $shouldThrottle = true;
            $throttleUntil = now()->addHour();
        } elseif ($this->limit_per_day && $this->current_day_count >= $this->limit_per_day) {
            $shouldThrottle = true;
            $throttleUntil = now()->addDay();
        }

        if ($shouldThrottle !== $this->is_throttled) {
            $this->update([
                'is_throttled' => $shouldThrottle,
                'throttle_until' => $throttleUntil,
            ]);
        }
    }

    /**
     * Get seconds until next request can be made
     */
    public function getWaitTimeSeconds(): int
    {
        if ($this->canMakeRequest()) {
            return 0;
        }

        $now = now();
        $waitTimes = [];

        if ($this->limit_per_minute && $this->current_minute_count >= $this->limit_per_minute && $this->minute_reset_at) {
            $waitTimes[] = max(0, $now->diffInSeconds($this->minute_reset_at, false));
        }

        if ($this->limit_per_hour && $this->current_hour_count >= $this->limit_per_hour && $this->hour_reset_at) {
            $waitTimes[] = max(0, $now->diffInSeconds($this->hour_reset_at, false));
        }

        if ($this->limit_per_day && $this->current_day_count >= $this->limit_per_day && $this->day_reset_at) {
            $waitTimes[] = max(0, $now->diffInSeconds($this->day_reset_at, false));
        }

        return empty($waitTimes) ? 60 : min($waitTimes);
    }

    /**
     * Initialize rate limit tracking for a source
     */
    public static function initializeForSource(CatalogSource $source, ?string $endpointPattern = null): self
    {
        $now = now();

        return static::create([
            'catalog_source_id' => $source->id,
            'endpoint_pattern' => $endpointPattern,
            'limit_per_minute' => $source->rate_limit_per_minute,
            'limit_per_hour' => null, // Can be configured separately
            'limit_per_day' => null,  // Can be configured separately
            'current_minute_count' => 0,
            'current_hour_count' => 0,
            'current_day_count' => 0,
            'minute_reset_at' => $now->copy()->addMinute(),
            'hour_reset_at' => $now->copy()->addHour(),
            'day_reset_at' => $now->copy()->addDay(),
            'is_throttled' => false,
        ]);
    }
}
