<?php

namespace App\Models\CatalogMaster;

use App\Models\CatalogImport\CatalogSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryExternalMapping extends Model
{
    use HasFactory;

    protected $table = 'category_external_mappings';

    public $timestamps = true;

    protected $fillable = [
        'category_id',
        'catalog_source_id',
        'external_category_id',
        'external_category_name',
        'external_category_path',
        'mapping_metadata',
        'is_primary',
    ];

    protected $casts = [
        'mapping_metadata' => 'array',
        'is_primary' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CatalogSource::class, 'catalog_source_id');
    }
}
