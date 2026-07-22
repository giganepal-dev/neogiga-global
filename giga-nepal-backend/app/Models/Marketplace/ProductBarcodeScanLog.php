<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product Barcode Scan Log Model
 * 
 * Tracks all barcode scan attempts for analytics, audit, and debugging.
 */
class ProductBarcodeScanLog extends Model
{
    protected $fillable = [
        'product_barcode_id',
        'barcode_value',
        'user_id',
        'pos_terminal_id',
        'marketplace_id',
        'warehouse_id',
        'scan_source',
        'was_successful',
        'failure_reason',
        'response_time_ms',
        'scanner_ip',
        'scanner_device_id',
        'context',
    ];

    protected $casts = [
        'was_successful' => 'boolean',
        'response_time_ms' => 'decimal:2',
        'context' => 'array',
    ];

    /**
     * Scan source constants
     */
    public const SOURCE_SCANNER = 'scanner';
    public const SOURCE_MOBILE = 'mobile';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_API = 'api';

    /**
     * Get the scanned barcode record if it exists
     */
    public function barcode(): BelongsTo
    {
        return $this->belongsTo(ProductBarcode::class, 'product_barcode_id');
    }

    /**
     * Get the user who performed the scan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the POS terminal used for scanning
     */
    public function posTerminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class);
    }

    /**
     * Get the marketplace where scan occurred
     */
    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    /**
     * Get the warehouse where scan occurred
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Scope to get only successful scans
     */
    public function scopeSuccessful($query)
    {
        return $query->where('was_successful', true);
    }

    /**
     * Scope to get failed scans
     */
    public function scopeFailed($query)
    {
        return $query->where('was_successful', false);
    }

    /**
     * Scope to filter by scan source
     */
    public function scopeOfSource($query, string $source)
    {
        return $query->where('scan_source', $source);
    }

    /**
     * Scope recent scans (last N minutes)
     */
    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
}
