<?php

namespace App\Models\Marketplace;

use App\Models\Manufacturer;
use App\Services\Product\ProductPublicationGate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'manufacturer_id',
        'category_id',
        'vendor_id',
        'manufacturer_name',
        'description',
        'short_description',
        'base_price',
        'cost_price',
        'sale_price',
        'normalized_mpn',
        'gtin',
        'hs_code',
        'eccn',
        'lifecycle_status',
        'source_name',
        'source_url',
        'source_file',
        'source_page_url',
        'downloaded_at',
        'imported_at',
        'data_year',
        'license_note',
        'confidence_level',
        'original_raw_value',
        'normalized_value',
        'last_verified_at',
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
        'downloaded_at' => 'datetime',
        'imported_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
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
        return $this->hasMany(ProductSpecGroup::class, 'category_id', 'category_id');
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function activeImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_active', true)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_active', true)->where('is_primary', true);
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

    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class)->orderBy('sort_order')->orderBy('id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProductApplication::class)->orderBy('sort_order')->orderBy('id');
    }

    public function priceBreaks(): HasMany
    {
        return $this->hasMany(ProductPriceBreak::class)->active();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class)->questionsOnly();
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ProductResource::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(\App\Models\Marketplace\ProductDocument::class)->orderByDesc('id');
    }

    public function scopePublished($query)
    {
        return app(ProductPublicationGate::class)->apply($query);
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
