<?php

namespace App\Models\Education;

use App\Models\Marketplace\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomLine extends Model
{
    protected $fillable = [
        'education_project_id', 'line_no', 'component_role', 'product_category',
        'preferred_manufacturer', 'preferred_mpn', 'neogiga_sku', 'quantity',
        'is_required', 'minimum_specification', 'compatibility_requirements',
        'preferred_product_id', 'alternative_product_ids', 'unit_price', 'extended_price',
        'in_local_stock', 'in_global_stock', 'lead_time_days', 'datasheet_url', 'product_notes',
    ];

    protected $casts = [
        'alternative_product_ids' => 'array', 'is_required' => 'boolean',
        'in_local_stock' => 'boolean', 'in_global_stock' => 'boolean',
        'quantity' => 'integer', 'lead_time_days' => 'integer',
        'unit_price' => 'decimal:4', 'extended_price' => 'decimal:4',
    ];

    public function project(): BelongsTo { return $this->belongsTo(EducationProject::class, 'education_project_id'); }
    public function preferredProduct(): BelongsTo { return $this->belongsTo(Product::class, 'preferred_product_id'); }

    public function scopeRequired($q) { return $q->where('is_required', true); }
    public function scopeOptional($q) { return $q->where('is_required', false); }
}
