<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDatasheet extends Model
{
    protected $fillable = [
        'product_id',
        'title',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'document_type',
        'description',
        'language',
        'is_public',
        'download_count',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_public' => 'boolean',
        'download_count' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }
}
