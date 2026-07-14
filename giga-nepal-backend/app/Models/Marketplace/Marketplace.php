<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Marketplace extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'country_id',
        'currency_id',
        'timezone',
        'locale',
        'is_active',
        'is_default',
        'allow_vendor_registration',
        'require_vendor_approval',
        'tax_rate',
        'supported_languages',
        'settings',
        'url_prefix',
        'regional_brand_name',
        'default_language',
        'launch_status',
        'global_fallback',
        'checkout_enabled',
        'redirect_enabled',
        'local_seller_support',
        'local_warehouse_support',
        'local_payment_support',
        // Domain/SEO configuration system
        'country_iso2', 'country_iso3', 'currency_code', 'currency_symbol',
        'domain', 'domain_mode', 'domain_prefix', 'generated_domain', 'canonical_domain',
        'force_https', 'redirect_to_canonical', 'www_redirect_mode', 'domain_verified_at',
        'ssl_status', 'is_domain_locked',
        'is_visible', 'allow_customer_registration', 'maintenance_mode', 'maintenance_message',
        'launch_at', 'disabled_at', 'disabled_reason',
        'seo_title', 'seo_description', 'seo_keywords', 'seo_h1', 'seo_canonical_url', 'seo_robots',
        'seo_og_title', 'seo_og_description', 'seo_og_image', 'seo_twitter_title',
        'seo_twitter_description', 'seo_twitter_image', 'seo_schema_json', 'seo_header_scripts',
        'seo_footer_scripts', 'sitemap_enabled', 'hreflang_enabled', 'indexable',
        'seo_is_auto_generated', 'seo_last_generated_at', 'seo_manual_override_fields',
        'seo_marketplace_name', 'has_local_warehouse', 'warehouse_display_name',
        'seo_fulfilment_phrase', 'seo_site_suffix',
        'short_description', 'marketplace_description', 'homepage_heading', 'homepage_subheading',
        'logo', 'favicon', 'banner_image', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'allow_vendor_registration' => 'boolean',
        'require_vendor_approval' => 'boolean',
        'tax_rate' => 'decimal:5',
        'supported_languages' => 'array',
        'settings' => 'array',
        'global_fallback' => 'boolean',
        'checkout_enabled' => 'boolean',
        'redirect_enabled' => 'boolean',
        'local_seller_support' => 'boolean',
        'local_warehouse_support' => 'boolean',
        'local_payment_support' => 'boolean',
        // Domain/SEO configuration system
        'force_https' => 'boolean',
        'redirect_to_canonical' => 'boolean',
        'is_domain_locked' => 'boolean',
        'domain_verified_at' => 'datetime',
        'is_visible' => 'boolean',
        'allow_customer_registration' => 'boolean',
        'maintenance_mode' => 'boolean',
        'launch_at' => 'datetime',
        'disabled_at' => 'datetime',
        'seo_schema_json' => 'array',
        'seo_manual_override_fields' => 'array',
        'sitemap_enabled' => 'boolean',
        'hreflang_enabled' => 'boolean',
        'indexable' => 'boolean',
        'seo_is_auto_generated' => 'boolean',
        'seo_last_generated_at' => 'datetime',
        'has_local_warehouse' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(MarketplaceDomain::class);
    }

    public function settingsRecord(): HasOne
    {
        return $this->hasOne(MarketplaceSetting::class);
    }

    public function taxZones(): HasMany
    {
        return $this->hasMany(TaxZone::class);
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class);
    }

    public function vendorApprovals(): HasMany
    {
        return $this->hasManyThrough(
            VendorMarketplaceApproval::class,
            Vendor::class,
            'id',
            'marketplace_id',
            'vendor_id',
            'id'
        );
    }
}
