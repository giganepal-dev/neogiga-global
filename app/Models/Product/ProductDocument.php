<?php

namespace App\Models\Product;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDocument extends BaseModel
{
    protected $table = 'product_documents';

    protected $fillable = [
        'product_id', 'document_type', 'title', 'file_path',
        'file_mime', 'file_size', 'sort_order', 'is_visible', 'metadata'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'is_visible' => 'boolean',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
