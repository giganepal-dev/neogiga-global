<?php

namespace App\Models\Inventory;

use App\Models\Marketplace\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Count Model
 * 
 * Represents a physical inventory counting session
 */
class StockCount extends Model
{
    protected $table = 'stock_counts';

    protected $fillable = [
        'warehouse_id',
        'warehouse_zone_id',
        'created_by',
        'approved_by',
        'count_number',
        'count_type',
        'status',
        'scheduled_date',
        'started_at',
        'completed_at',
        'approved_at',
        'posted_at',
        'total_items_expected',
        'total_items_counted',
        'total_items_matched',
        'total_items_variance',
        'variance_value',
        'notes',
        'adjustment_reason',
        'scope',
        'metadata',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'total_items_expected' => 'integer',
        'total_items_counted' => 'integer',
        'total_items_matched' => 'integer',
        'total_items_variance' => 'integer',
        'variance_value' => 'decimal:4',
        'scope' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Count type constants
     */
    public const TYPE_SCHEDULED = 'scheduled';
    public const TYPE_CYCLE = 'cycle';
    public const TYPE_SPOT_CHECK = 'spot_check';
    public const TYPE_ANNUAL = 'annual';
    public const TYPE_ADJUSTMENT = 'adjustment';

    /**
     * Status constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COUNTING_COMPLETE = 'counting_complete';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_POSTED = 'posted';

    public static function getCountTypes(): array
    {
        return [
            self::TYPE_SCHEDULED => 'Scheduled Count',
            self::TYPE_CYCLE => 'Cycle Count',
            self::TYPE_SPOT_CHECK => 'Spot Check',
            self::TYPE_ANNUAL => 'Annual Inventory',
            self::TYPE_ADJUSTMENT => 'Adjustment',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COUNTING_COMPLETE => 'Counting Complete',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_POSTED => 'Posted',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse\WarehouseZone::class, 'warehouse_zone_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class, 'stock_count_id');
    }

    /**
     * Get match rate percentage
     */
    public function getMatchRateAttribute(): float
    {
        if ($this->total_items_counted === 0) {
            return 0.0;
        }
        return round(($this->total_items_matched / $this->total_items_counted) * 100, 2);
    }

    /**
     * Check if count is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if count is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COUNTING_COMPLETE;
    }

    /**
     * Check if count is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if count is posted (adjustments applied)
     */
    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    /**
     * Start the stock count
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark counting as complete
     */
    public function markCountingComplete(): void
    {
        $this->update([
            'status' => self::STATUS_COUNTING_COMPLETE,
            'completed_at' => now(),
        ]);
    }

    /**
     * Approve the stock count
     */
    public function approve(int $approvedByUserId): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedByUserId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Post adjustments to inventory
     * This should be called within a transaction
     */
    public function postAdjustments(): void
    {
        // This would trigger inventory movements for each variance
        // Implementation in StockMovementService
        $this->update([
            'status' => self::STATUS_POSTED,
            'posted_at' => now(),
        ]);
    }

    /**
     * Scope for counts in progress
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope for counts pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_COUNTING_COMPLETE);
    }

    /**
     * Scope for counts requiring review
     */
    public function scopeRequiringReview($query)
    {
        return $query->whereIn('status', [self::STATUS_COUNTING_COMPLETE, self::STATUS_REVIEWED]);
    }
}
