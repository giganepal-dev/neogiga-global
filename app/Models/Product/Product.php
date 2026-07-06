<?php

namespace App\Models\Product;

use App\Models\BaseModel;
use App\Models\Vendor\Vendor;
use App\Models\Marketplace\Marketplace;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends BaseModel
{
    protected $table = 'products';

    protected $fillable = [
        'vendor_id', 'brand_id', 'category_id', 'name', 'slug', 'sku_global',
        'description', 'short_description', 'type', 'status',
        'is_visible', 'is_featured', 'is_digital', 'requires_shipping',
        'weight', 'weight_unit', 'length', 'width', 'height', 'dimension_unit',
        'tax_class', 'hs_code', 'country_of_origin', 'metadata'
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
        'is_digital' => 'boolean',
        'requires_shipping' => 'boolean',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'metadata' => 'array',
    ];

    const TYPE_SIMPLE = 'simple';
    const TYPE_VARIABLE = 'variable';
    const TYPE_BUNDLE = 'bundle';
    const TYPE_KIT = 'kit';
    const TYPE_SERVICE = 'service';
    const TYPE_DIGITAL = 'digital';

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ARCHIVED = 'archived';

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function brand()
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function specGroups(): HasMany
    {
        return $this->hasMany(ProductSpecGroup::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class);
    }

    public function seoMeta(): HasOne
    {
        return $this->hasOne(ProductSeoMeta::class);
    }

    public function approvalStatus(): HasOne
    {
        return $this->hasOne(ProductApprovalStatus::class);
    }

    public function relatedItems(): HasMany
    {
        return $this->hasMany(ProductRelatedItem::class, 'product_id');
    }

    public function compatibleProducts(): HasMany
    {
        return $this->hasMany(ProductCompatibility::class, 'product_id');
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(ProductBomItem::class, 'product_id');
    }

    public function lmsLinks(): HasMany
    {
        return $this->hasMany(ProductLmsLink::class);
    }

    public function marketplacePrices(): HasMany
    {
        return $this->hasMany(\App\Models\Pricing\MarketplaceProductPrice::class);
    }

    public function vendorPrices(): HasMany
    {
        return $this->hasMany(\App\Models\Pricing\VendorProductPrice::class);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(\App\Models\Inventory\InventoryStock::class);
    }
}
