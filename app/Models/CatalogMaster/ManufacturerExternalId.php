<?php

namespace App\Models\CatalogMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerExternalId extends Model
{
    use HasFactory;

    protected $table = 'manufacturer_external_ids';

    public $timestamps = true;

    protected $fillable = [
        'manufacturer_id',
        'source_name',
        'external_id',
        'source_url',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }
}
