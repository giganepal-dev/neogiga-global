<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PcbQuoteConfiguration extends Model
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
        'project_id', 'created_by_id', 'organization_id',
        'board_type', 'designs_per_panel', 'quantity',
        'length_mm', 'width_mm', 'thickness_mm',
        'layer_count', 'substrate_material', 'tg_value',
        'outer_copper_oz', 'inner_copper_oz',
        'min_trace_mm', 'min_spacing_mm', 'min_hole_mm',
        'solder_mask_color', 'silkscreen_color',
        'surface_finish', 'gold_thickness_um',
        'impedance_control', 'impedance_requirements',
        'via_covering', 'blind_buried_vias', 'hdi',
        'edge_plating', 'castellated_holes', 'countersink',
        'panelization_type',
        'aoi_testing', 'electrical_test', 'electrical_test_type',
        'ul_date_marking', 'customer_marking', 'packaging_type',
        'production_speed', 'lead_time_days',
        'status',
        'setup_charge', 'engineering_charge',
        'fabrication_unit_price', 'total_fabrication_price',
        'currency', 'requires_engineering_quote', 'engineering_notes',
        'submitted_at', 'quoted_at', 'quote_valid_until',
        'customer_rejected_at', 'customer_notes',
    ];

    protected $casts = [
        'designs_per_panel' => 'integer',
        'quantity' => 'integer',
        'layer_count' => 'integer',
        'tg_value' => 'decimal:1',
        'impedance_control' => 'boolean',
        'impedance_requirements' => 'array',
        'blind_buried_vias' => 'boolean',
        'hdi' => 'boolean',
        'edge_plating' => 'boolean',
        'castellated_holes' => 'boolean',
        'countersink' => 'boolean',
        'aoi_testing' => 'boolean',
        'electrical_test' => 'boolean',
        'ul_date_marking' => 'boolean',
        'customer_marking' => 'boolean',
        'setup_charge' => 'decimal:2',
        'engineering_charge' => 'decimal:2',
        'fabrication_unit_price' => 'decimal:2',
        'total_fabrication_price' => 'decimal:2',
        'requires_engineering_quote' => 'boolean',
        'submitted_at' => 'datetime',
        'quoted_at' => 'datetime',
        'quote_valid_until' => 'date',
        'customer_rejected_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(PcbQuoteLineItem::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(PcbOrder::class, 'quote_id');
    }

    public function getTotalPriceAttribute(): ?float
    {
        $basePrice = $this->total_fabrication_price ?? 0;
        $setupCharge = $this->setup_charge ?? 0;
        $engineeringCharge = $this->engineering_charge ?? 0;
        
        $lineItemsTotal = $this->lineItems()->sum('total_price');

        return $basePrice + $setupCharge + $engineeringCharge + $lineItemsTotal;
    }

    public function requiresReview(): bool
    {
        return $this->requires_engineering_quote || 
               $this->hdi || 
               $this->blind_buried_vias ||
               $this->edge_plating ||
               in_array($this->board_type, ['rigid_flex', 'flex', 'ceramic']);
    }

    public function getStatusBadgeAttribute(): string
    {
        $colors = [
            'draft' => 'gray',
            'submitted' => 'blue',
            'quoted' => 'green',
            'approved' => 'green',
            'rejected' => 'red',
        ];

        return $colors[$this->status] ?? 'gray';
    }
}
