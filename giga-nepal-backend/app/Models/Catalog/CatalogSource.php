<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogSource extends Model
{
    protected $table = 'catalog_sources';

    protected $guarded = [];

    protected $casts = ['active' => 'boolean', 'import_enabled' => 'boolean', 'media_download_enabled' => 'boolean', 'catalogue_policy' => 'array'];

    public function supplierProducts(): HasMany
    {
        return $this->hasMany(SupplierProduct::class, 'catalog_source_id');
    }
}
