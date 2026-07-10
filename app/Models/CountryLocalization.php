<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Country Localization Model
 * 
 * Contains all localization settings for a country-specific storefront.
 * Handles domain routing, SEO, formatting, payment/shipping methods.
 */
class CountryLocalization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'country_id',
        'domain',
        'path_prefix',
        'seo_title_prefix',
        'seo_title_suffix',
        'default_meta_description',
        'hreflang_tags',
        'canonical_domain',
        'auto_redirect',
        'show_currency_selector',
        'show_language_selector',
        'date_format',
        'time_format',
        'number_format_decimal',
        'number_format_thousands',
        'address_format',
        'payment_methods',
        'shipping_methods',
        'free_shipping_threshold',
        'customer_support_email',
        'customer_support_phone',
        'business_hours',
        'legal_notice',
        'terms_url',
        'privacy_url',
        'is_active',
    ];

    protected $casts = [
        'hreflang_tags' => 'array',
        'auto_redirect' => 'boolean',
        'show_currency_selector' => 'boolean',
        'show_language_selector' => 'boolean',
        'payment_methods' => 'array',
        'shipping_methods' => 'array',
        'free_shipping_threshold' => 'decimal:2',
        'business_hours' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the country this localization belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get price lists for this country.
     */
    public function priceLists(): HasMany
    {
        return $this->country->priceLists();
    }

    /**
     * Check if domain matches.
     */
    public function matchesDomain(string $domain): bool
    {
        if (!$this->domain) {
            return false;
        }

        return strtolower($this->domain) === strtolower($domain);
    }

    /**
     * Check if path prefix matches.
     */
    public function matchesPath(string $path): bool
    {
        if (!$this->path_prefix) {
            return false;
        }

        return strpos($path, $this->path_prefix) === 0;
    }

    /**
     * Get formatted SEO title.
     */
    public function buildSeoTitle(string $pageTitle): string
    {
        $parts = [];

        if ($this->seo_title_prefix) {
            $parts[] = $this->seo_title_prefix;
        }

        $parts[] = $pageTitle;

        if ($this->seo_title_suffix) {
            $parts[] = $this->seo_title_suffix;
        }

        return implode(' | ', $parts);
    }

    /**
     * Get hreflang tag for a language code.
     */
    public function getHreflangUrl(string $langCode): ?string
    {
        if (empty($this->hreflang_tags) || !isset($this->hreflang_tags[$langCode])) {
            return null;
        }

        $basePath = $this->hreflang_tags[$langCode];
        
        if ($this->canonical_domain) {
            return "https://{$this->canonical_domain}{$basePath}";
        }

        return $basePath;
    }

    /**
     * Get all hreflang tags for SEO.
     */
    public function getHreflangTags(): array
    {
        return $this->hreflang_tags ?? [];
    }

    /**
     * Format date according to country settings.
     */
    public function formatDate(\DateTime $date): string
    {
        return $date->format($this->date_format);
    }

    /**
     * Format time according to country settings.
     */
    public function formatTime(\DateTime $date): string
    {
        return $date->format($this->time_format);
    }

    /**
     * Format number according to country settings.
     */
    public function formatNumber(float $number, int $decimals = 2): string
    {
        return number_format(
            $number,
            $decimals,
            $this->number_format_decimal,
            $this->number_format_thousands
        );
    }

    /**
     * Format address from components.
     */
    public function formatAddress(array $addressData): string
    {
        if (!$this->address_format) {
            // Default format
            return trim(sprintf(
                "%s\n%s\n%s %s\n%s",
                $addressData['name'] ?? '',
                $addressData['street'] ?? '',
                $addressData['city'] ?? '',
                $addressData['postal_code'] ?? '',
                $addressData['country'] ?? ''
            ));
        }

        // Replace placeholders in address format
        $format = $this->address_format;
        
        foreach ($addressData as $key => $value) {
            $format = str_replace("{{$key}}", $value ?? '', $format);
        }

        return $format;
    }

    /**
     * Check if payment method is available.
     */
    public function isPaymentMethodAvailable(string $method): bool
    {
        if (empty($this->payment_methods)) {
            return true; // All methods available if not specified
        }

        return in_array($method, $this->payment_methods);
    }

    /**
     * Check if shipping method is available.
     */
    public function isShippingMethodAvailable(string $method): bool
    {
        if (empty($this->shipping_methods)) {
            return true; // All methods available if not specified
        }

        return in_array($method, $this->shipping_methods);
    }

    /**
     * Get available payment methods.
     */
    public function getAvailablePaymentMethods(): array
    {
        return $this->payment_methods ?? [];
    }

    /**
     * Get available shipping methods.
     */
    public function getAvailableShippingMethods(): array
    {
        return $this->shipping_methods ?? [];
    }

    /**
     * Scope to get only active localizations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Find localization by domain.
     */
    public static function findByDomain(string $domain): ?self
    {
        return self::where('domain', $domain)
            ->orWhere('canonical_domain', $domain)
            ->first();
    }

    /**
     * Find localization by path prefix.
     */
    public static function findByPath(string $path): ?self
    {
        return self::whereNotNull('path_prefix')
            ->get()
            ->first(function ($loc) use ($path) {
                return $loc->matchesPath($path);
            });
    }

    /**
     * Get or create localization for country.
     */
    public static function getOrCreateForCountry(Country $country): self
    {
        return self::firstOrCreate(
            ['country_id' => $country->id],
            [
                'path_prefix' => '/' . strtolower($country->iso_code_2) . '/',
                'is_active' => true,
            ]
        );
    }
}
