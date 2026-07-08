<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecTemplateField extends Model
{
    protected $fillable = [
        'template_id',
        'field_name',
        'field_label',
        'field_type',
        'unit',
        'options',
        'validation_rules',
        'help_text',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(CategorySpecTemplate::class, 'template_id');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class, 'template_field_id');
    }

    public function scopeByTemplate($query, $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
