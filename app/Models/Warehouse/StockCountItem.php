<?php

namespace App\Models\Warehouse;

use App\Models\User;
use App\Models\Marketplace\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockCountItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'stock_count_items';

    public $incrementing = false;
    
    protected $fillable = [
        'stock_count_id',
        'product_id',
        'bin_id',
        'batch_id',
        'serial_number',
        'system_quantity',
        'counted_quantity',
        'variance_quantity',
        'unit_cost',
        'variance_value',
        'variance_reason',
        'notes',
        'counted_by',
        'counted_at',
        'is_adjusted',
        'adjustment_movement_id',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:4',
        'counted_quantity' => 'decimal:4',
        'variance_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'variance_value' => 'decimal:2',
        'counted_at' => 'datetime',
        'is_adjusted' => 'boolean',
    ];

    const VARIANCE_NOT_FOUND = 'not_found';
    const VARIANCE_FOUND_EXTRA = 'found_extra';
    const VARIANCE_DAMAGED = 'damaged';
    const VARIANCE_EXPIRED = 'expired';
    const VARIANCE_MISPLACED = 'misplaced';
    const VARIANCE_DATA_ERROR = 'data_error';
    const VARIANCE_THEFT = 'theft';
    const VARIANCE_OTHER = 'other';

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bin(): BelongsTo
    {
        return $this->belongsTo(WarehouseBin::class, 'bin_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function adjustmentMovement(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\InventoryMovement::class, 'adjustment_movement_id');
    }

    public function scopeForStockCount($query, $stockCountId)
    {
        return $query->where('stock_count_id', $stockCountId);
    }

    public function scopeWithVariance($query)
    {
        return $query->where('variance_quantity', '!=', 0);
    }

    public function scopeAdjusted($query)
    {
        return $query->where('is_adjusted', true);
    }

    public function scopeNotAdjusted($query)
    {
        return $query->where('is_adjusted', false);
    }

    public function hasVariance(): bool
    {
        return abs($this->variance_quantity) > 0.0001;
    }

    public function markAsCounted(float $quantity, ?User $user = null): void
    {
        $this->update([
            'counted_quantity' => $quantity,
            'variance_quantity' => $quantity - $this->system_quantity,
            'variance_value' => ($quantity - $this->system_quantity) * ($this->unit_cost ?? 0),
            'counted_by' => $user?->id,
            'counted_at' => now(),
        ]);
    }

    public function markAsAdjusted(int $movementId): void
    {
        $this->update([
            'is_adjusted' => true,
            'adjustment_movement_id' => $movementId,
        ]);
    }
}
