<?php

namespace App\Models\CatalogImport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CatalogSourceWebhook Model
 * 
 * Configures webhook endpoints for real-time updates from catalog sources.
 */
class CatalogSourceWebhook extends Model
{
    protected $fillable = [
        'catalog_source_id',
        'webhook_url',
        'external_webhook_url',
        'event_type',
        'event_filters',
        'secret_token',
        'active',
        'last_received_at',
        'total_received',
        'successful_processed',
        'failed_processed',
        'last_error',
    ];

    protected $casts = [
        'event_filters' => 'array',
        'active' => 'boolean',
        'last_received_at' => 'datetime',
    ];

    /**
     * Event type constants
     */
    const EVENT_PRODUCT_UPDATE = 'product_update';
    const EVENT_PRICE_CHANGE = 'price_change';
    const EVENT_STOCK_CHANGE = 'stock_change';
    const EVENT_LIFECYCLE_CHANGE = 'lifecycle_change';
    const EVENT_FULL_SYNC = 'full_sync';

    public function source(): BelongsTo
    {
        return $this->belongsTo(CatalogSource::class, 'catalog_source_id');
    }

    /**
     * Record a received webhook
     */
    public function recordReceived(bool $success = true, ?string $error = null): void
    {
        $this->increment('total_received');
        
        if ($success) {
            $this->increment('successful_processed');
            $this->update(['last_received_at' => now()]);
        } else {
            $this->increment('failed_processed');
            $this->update([
                'last_error' => $error,
                'last_received_at' => now(),
            ]);
        }
    }

    /**
     * Verify HMAC signature of incoming webhook
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        if (empty($this->secret_token)) {
            return true; // No verification if no secret configured
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->secret_token);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Check if webhook matches event filters
     */
    public function matchesEvent(array $eventData): bool
    {
        if (empty($this->event_filters)) {
            return true; // No filters means accept all
        }

        foreach ($this->event_filters as $field => $expectedValue) {
            if (!isset($eventData[$field]) || $eventData[$field] !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Scope to get active webhooks for an event type
     */
    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType)
            ->where('active', true);
    }

    /**
     * Scope to get webhooks with recent failures
     */
    public function scopeWithRecentFailures($query, int $threshold = 5)
    {
        return $query->whereColumn('failed_processed', '>', $threshold);
    }
}
