<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcbOrder extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->order_number)) {
                $model->order_number = 'PCB-ORD-'.strtoupper(Str::random(8));
            }
        });
    }

    protected $fillable = [
        'project_id', 'quote_id', 'user_id', 'order_number',
        'status', 'payment_status', 'currency', 'total_amount', 'customer_notes',
        'milestones', 'estimated_ship_date', 'tracking_number', 'tracking_carrier',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'milestones' => 'array',
        'estimated_ship_date' => 'datetime',
    ];

    /**
     * Default manufacturing milestones for a new order.
     */
    public static function defaultMilestones(): array
    {
        return [
            ['label' => 'Order confirmed', 'status' => 'done', 'date' => now()->toISOString()],
            ['label' => 'Files in fabrication queue', 'status' => 'current', 'date' => null],
            ['label' => 'Lamination & drilling', 'status' => 'pending', 'date' => null],
            ['label' => 'Plating & etch', 'status' => 'pending', 'date' => null],
            ['label' => 'Solder mask & silkscreen', 'status' => 'pending', 'date' => null],
            ['label' => 'Electrical test', 'status' => 'pending', 'date' => null],
            ['label' => 'Final inspection', 'status' => 'pending', 'date' => null],
            ['label' => 'Packing & shipping', 'status' => 'pending', 'date' => null],
        ];
    }

    public function getMilestonesAttribute($value): array
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return is_array($decoded) ? $decoded : self::defaultMilestones();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class, 'project_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(PcbQuoteConfiguration::class, 'quote_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }
}
