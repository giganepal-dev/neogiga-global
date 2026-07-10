<?php

namespace App\Models\CatalogMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryAttribute extends Model
{
    use HasFactory;

    protected $table = 'category_attributes';

    public $timestamps = true;

    protected $fillable = [
        'category_id',
        'attribute_group_id',
        'attribute_id',
        'is_required',
        'is_filterable',
        'is_comparable',
        'is_searchable',
        'visible_on_product_page',
        'display_position',
        'display_label',
        'validation_rules',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_filterable' => 'boolean',
        'is_comparable' => 'boolean',
        'is_searchable' => 'boolean',
        'visible_on_product_page' => 'boolean',
        'display_position' => 'integer',
        'validation_rules' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attributeGroup(): BelongsTo
    {
        return $this->belongsTo(CategoryAttributeGroup::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
