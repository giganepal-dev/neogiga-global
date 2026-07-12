<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductResource extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'external_url',
        'github_repo',
        'language',
        'version',
        'is_downloadable',
        'download_count',
        'is_verified',
        'metadata',
    ];

    protected $casts = [
        'is_downloadable' => 'boolean',
        'download_count' => 'integer',
        'is_verified' => 'boolean',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeDatasheets($query)
    {
        return $query->where('type', 'datasheet');
    }

    public function scopeLibraries($query)
    {
        return $query->whereIn('type', ['arduino_library', 'platformio_library', 'circuitpython_library']);
    }

    public function scopeCodeExamples($query)
    {
        return $query->whereIn('type', ['example_code', 'github_example']);
    }

    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }
}
