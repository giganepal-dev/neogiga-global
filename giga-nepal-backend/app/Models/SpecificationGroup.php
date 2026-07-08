<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SpecificationGroup extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'sort_order',
        'is_expanded',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_expanded' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(SpecTemplateField::class, 'specification_group_fields', 'group_id', 'template_field_id')
                    ->withPivot('sort_order')
                    ->orderByPivot('sort_order');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
