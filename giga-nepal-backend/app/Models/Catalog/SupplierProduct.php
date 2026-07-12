<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProduct extends Model
{
    protected $guarded = [];

    protected $casts = ['source_category_path_json' => 'array', 'raw_payload_json' => 'array', 'first_seen_at' => 'datetime', 'last_seen_at' => 'datetime', 'last_changed_at' => 'datetime', 'imported_at' => 'datetime'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(CatalogSource::class, 'catalog_source_id');
    }
}
