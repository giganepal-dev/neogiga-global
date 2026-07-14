<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerAlias extends Model
{
    protected $fillable = [
        'manufacturer_id',
        'alias',
        'normalized_alias',
        'source_name',
        'source_url',
        'confidence_score',
    ];

    protected $casts = [
        'confidence_score' => 'integer',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }
}
