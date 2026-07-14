<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'imageable_type',
        'imageable_id',
        'url',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'alt_text',
        'caption',
        'sort_order',
        'is_primary',
        'is_active',
        'original_url',
        'source_url',
        'source_name',
        'source_file',
        'source_page_url',
        'source_license',
        'license_note',
        'copyright',
        'checksum',
        'width',
        'height',
        'storage_disk',
        'downloaded_at',
        'imported_at',
        'data_year',
        'confidence_level',
        'original_raw_value',
        'normalized_value',
        'region_visibility',
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'data_year' => 'integer',
        'downloaded_at' => 'datetime',
        'imported_at' => 'datetime',
        'region_visibility' => 'array',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function imageable(): MorphMany
    {
        return $this->morphMany(Product::class, 'imageable');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function publicUrl(): string
    {
        $path = trim((string) ($this->file_path ?: $this->original_url ?: $this->source_url));
        if ($path === '') {
            return asset('images/products/neogiga-product-placeholder-2026.png');
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (Str::startsWith($path, '/')) {
            return url($path);
        }

        if (Str::startsWith($path, ['images/', 'build/', 'storage/'])) {
            return asset($path);
        }

        $disk = $this->storage_disk ?: data_get($this->metadata, 'disk', 'public');
        try {
            return Storage::disk($disk)->url($path);
        } catch (\Throwable) {
            return asset('storage/'.ltrim($path, '/'));
        }
    }
}
