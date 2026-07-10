<?php

namespace App\Models\CatalogMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandExternalId extends Model
{
    use HasFactory;

    protected $table = 'brand_external_ids';

    public $timestamps = true;

    protected $fillable = [
        'brand_id',
        'source_name',
        'external_id',
        'source_url',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
