<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sku',
        'vendor_sku',
        'mpn',
        'type',
        'status',
        'brand_id',
        'category_id',
        'vendor_id',
        'description',
        'short_description',
        'is_featured',
        'is_virtual',
        'is_downloadable',
        'track_inventory',
        'stock_quantity',
        'low_stock_threshold',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimension_unit',
        'tax_class_id',
        'is_taxable',
        'marketplace_visibility',
        'attributes',
        'metadata',
        'seo_meta',
        'approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_virtual' => 'boolean',
        'is_downloadable' => 'boolean',
        'is_taxable' => 'boolean',
        'track_inventory' => 'boolean',
        'marketplace_visibility' => 'array',
        'attributes' => 'array',
        'metadata' => 'array',
        'seo_meta' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function specGroups(): HasMany
    {
        return $this->hasMany(ProductSpecGroup::class);
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function marketplacePrices(): HasMany
    {
        return $this->hasMany(MarketplaceProductPrice::class);
    }

    public function vendorPrices(): HasMany
    {
        return $this->hasMany(VendorProductPrice::class);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_related_items', 'product_id', 'related_product_id');
    }

    public function compatibleProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_compatibility', 'product_id', 'compatible_product_id');
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(ProductBomItem::class);
    }

    public function lmsLinks(): HasMany
    {
        return $this->hasMany(ProductLmsLink::class);
    }

    public function seoMeta(): HasOne
    {
        return $this->hasOne(ProductSeoMeta::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByBrand($query, $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
