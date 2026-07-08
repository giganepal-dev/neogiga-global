<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecificationGroupField extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'group_id',
        'template_field_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(SpecificationGroup::class);
    }

    public function templateField(): BelongsTo
    {
        return $this->belongsTo(SpecTemplateField::class, 'template_field_id');
    }
}
