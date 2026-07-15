<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical Product - Master product record
 * 
 * This is the single source of truth for a product, independent of sellers,
 * warehouses, or regions. Multiple sellers can offer the same canonical product.
 */
class CanonicalProduct extends Model
{
    use SoftDeletes;

    protected $table = 'canonical_products';

    protected $fillable = [
        // Core identification
        'name',
        'slug',
        'sku',
        'mpn',
        'gtin',
        'upc',
        'ean',
        'isbn',
        
        // Brand & Manufacturer
        'brand_id',
        'manufacturer_id',
        
        // Classification
        'category_id',
        'product_type',
        'series',
        'family',
        'model',
        
        // Descriptions
        'short_description',
        'description',
        'features',
        'applications',
        
        // Specifications
        'specifications',
        'attributes',
        
        // Variants
        'has_variants',
        'variant_options',
        
        // Packaging & Ordering
        'packaging_type',
        'moq',
        'order_multiple',
        'lead_time_days',
        
        // Origin & Compliance
        'country_of_origin_id',
        'manufacturing_country_id',
        'warranty_period',
        'condition',
        'lifecycle_status',
        'rohs_compliant',
        'reach_compliant',
        'export_control_class',
        'reach_svhc',
        
        // Status & Workflow
        'status',
        'is_featured',
        'is_active',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'published_at',
        
        // SEO
        'meta_title',
        'meta_description',
        'meta_keywords',
        'seo_overrides',
        
        // Media
        'primary_image_id',
        'datasheet_count',
        'image_count',
        
        // Relationships
        'related_products_count',
        'compatible_products_count',
        'alternates_count',
        
        // Usage
        'bom_usage_count',
        
        // Quality
        'completeness_score',
        'missing_fields',
        'quality_warnings',
        
        // Duplicate detection
        'normalized_name',
        'name_hash',
        
        // Audit
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'features' => 'array',
        'applications' => 'array',
        'specifications' => 'array',
        'attributes' => 'array',
        'has_variants' => 'boolean',
        'variant_options' => 'array',
        'moq' => 'integer',
        'order_multiple' => 'integer',
        'lead_time_days' => 'integer',
        'rohs_compliant' => 'boolean',
        'reach_compliant' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'seo_overrides' => 'array',
        'primary_image_id' => 'integer',
        'datasheet_count' => 'integer',
        'image_count' => 'integer',
        'related_products_count' => 'integer',
        'compatible_products_count' => 'integer',
        'alternates_count' => 'integer',
        'bom_usage_count' => 'integer',
        'completeness_score' => 'integer',
        'missing_fields' => 'array',
        'quality_warnings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    
    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'manufacturer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function countryOfOrigin(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_of_origin_id');
    }

    public function manufacturingCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'manufacturing_country_id');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'canonical_product_id');
    }

    public function sellerOffers(): HasMany
    {
        return $this->hasMany(SellerOffer::class, 'canonical_product_id');
    }

    public function regionalInventory(): HasMany
    {
        return $this->hasMany(RegionalInventory::class, 'canonical_product_id');
    }

    public function regionalPrices(): HasMany
    {
        return $this->hasMany(RegionalPrice::class, 'canonical_product_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'canonical_product_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class, 'canonical_product_id');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(ProductRelationship::class, 'canonical_product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'canonical_product_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class, 'canonical_product_id');
    }

    public function approvalHistory(): HasMany
    {
        return $this->hasMany(ProductApprovalHistory::class, 'canonical_product_id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'id', 'primary_image_id');
    }

    /**
     * Scopes
     */
    
    public function scopePublished($query)
    {
        return $query->where('status', 'approved')
                     ->where('is_active', true)
                     ->whereNotNull('published_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

    public function scopeByManufacturer($query, $manufacturerId)
    {
        return $query->where('manufacturer_id', $manufacturerId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('mpn', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('gtin', 'like', "%{$search}%");
        });
    }

    public function scopeByLifecycleStatus($query, $status)
    {
        return $query->where('lifecycle_status', $status);
    }

    public function scopeWithStock($query)
    {
        return $query->whereHas('sellerOffers', function ($q) {
            $q->where('stock_quantity', '>', 0)
              ->where('status', 'active');
        });
    }

    /**
     * Accessors & Mutators
     */
    
    public function getIsAvailableAttribute(): bool
    {
        return $this->sellerOffers()
            ->where('status', 'active')
            ->where('stock_quantity', '>', 0)
            ->exists();
    }

    public function getMinPriceAttribute(): ?float
    {
        return $this->sellerOffers()
            ->where('status', 'active')
            ->min('base_price');
    }

    public function getTotalStockAttribute(): int
    {
        return $this->sellerOffers()
            ->where('status', 'active')
            ->sum('stock_quantity');
    }

    /**
     * Business Logic
     */
    
    public function calculateCompletenessScore(): int
    {
        $score = 0;
        $maxScore = 100;
        
        // Basic info (20 points)
        if ($this->name) $score += 5;
        if ($this->description) $score += 5;
        if ($this->short_description) $score += 5;
        if ($this->brand_id) $score += 5;
        
        // Identification (20 points)
        if ($this->mpn) $score += 7;
        if ($this->sku) $score += 7;
        if ($this->gtin) $score += 6;
        
        // Classification (15 points)
        if ($this->category_id) $score += 8;
        if ($this->product_type) $score += 7;
        
        // Media (15 points)
        if ($this->image_count > 0) $score += min(10, $this->image_count);
        if ($this->datasheet_count > 0) $score += 5;
        
        // Specifications (15 points)
        if ($this->specifications && count($this->specifications) > 0) {
            $score += min(15, count($this->specifications) * 2);
        }
        
        // Compliance (15 points)
        if ($this->rohs_compliant !== null) $score += 5;
        if ($this->reach_compliant !== null) $score += 5;
        if ($this->country_of_origin_id) $score += 5;
        
        $this->completeness_score = min($maxScore, $score);
        $this->save();
        
        return $score;
    }

    public function identifyMissingFields(): array
    {
        $missing = [];
        
        if (!$this->name) $missing[] = 'name';
        if (!$this->description) $missing[] = 'description';
        if (!$this->brand_id) $missing[] = 'brand';
        if (!$this->category_id) $missing[] = 'category';
        if (!$this->mpn) $missing[] = 'mpn';
        if ($this->image_count === 0) $missing[] = 'images';
        if ($this->datasheet_count === 0) $missing[] = 'datasheet';
        
        $this->missing_fields = $missing;
        $this->save();
        
        return $missing;
    }

    public static function findByMpn(string $mpn): ?self
    {
        return self::where('mpn', $mpn)->first();
    }

    public static function findByGtin(string $gtin): ?self
    {
        return self::where(function ($q) use ($gtin) {
            $q->where('gtin', $gtin)
              ->orWhere('upc', $gtin)
              ->orWhere('ean', $gtin);
        })->first();
    }
}
