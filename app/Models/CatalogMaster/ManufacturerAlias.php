<?php

namespace App\Models\CatalogMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerAlias extends Model
{
    use HasFactory;

    protected $table = 'manufacturer_aliases';

    public $timestamps = true;

    protected $fillable = [
        'manufacturer_id',
        'alias',
        'source',
        'confidence_score',
        'is_primary',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:4',
        'is_primary' => 'boolean',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }
}
