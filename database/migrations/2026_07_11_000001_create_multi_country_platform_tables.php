<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Countries table - Core geographic entities
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('iso_code_2', 2)->unique(); // US, GB, DE, etc.
            $table->string('iso_code_3', 3)->unique(); // USA, GBR, DEU, etc.
            $table->string('numeric_code', 3)->nullable(); // 840, 826, 276, etc.
            $table->string('phone_code', 10); // +1, +44, +49, etc.
            $table->string('capital', 100)->nullable();
            $table->string('currency_code', 3); // USD, GBP, EUR, etc.
            $table->string('currency_symbol', 10); // $, £, €, etc.
            $table->string('tld', 10)->nullable(); // .us, .uk, .de, etc.
            $table->string('native_name', 100)->nullable();
            $table->string('region', 50)->nullable(); // Europe, Asia, etc.
            $table->string('subregion', 50)->nullable(); // Western Europe, Southern Asia, etc.
            $table->json('languages')->nullable(); // ['en', 'de', 'fr']
            $table->string('timezone', 50)->nullable();
            $table->json('states')->nullable(); // State/province data
            $table->boolean('is_active')->default(true);
            $table->boolean('is_eu')->default(false); // EU member for VAT rules
            $table->boolean('requires_import_license')->default(false);
            $table->decimal('default_vat_rate', 5, 2)->default(0.00);
            $table->decimal('default_import_duty_rate', 5, 2)->default(0.00);
            $table->string('hs_code_prefix', 6)->nullable(); // First 6 digits default
            $table->boolean('allows_marketplace')->default(true);
            $table->boolean('allows_b2b')->default(true);
            $table->boolean('allows_b2c')->default(true);
            $table->json('restricted_categories')->nullable(); // Categories not allowed
            $table->json('compliance_requirements')->nullable(); // CE, FCC, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->index('iso_code_2');
            $table->index('iso_code_3');
            $table->index('currency_code');
            $table->index('is_active');
        });

        // Currencies table - Multi-currency support
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // USD, EUR, GBP, etc.
            $table->string('name', 100);
            $table->string('symbol', 10);
            $table->string('symbol_position', 10)->default('before'); // before, after, space_before, space_after
            $table->integer('decimal_places')->default(2);
            $table->integer('exchange_rate')->default(100000); // Stored as integer (rate * 100000) for precision
            $table->date('exchange_rate_date')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_crypto')->default(false);
            $table->string('central_bank_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
        });

        // Country-Currency relationship with overrides
        Schema::create('country_currency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->integer('exchange_rate')->nullable(); // Override for specific country
            $table->date('exchange_rate_date')->nullable();
            $table->decimal('conversion_fee', 5, 4)->default(0.00); // Fee for currency conversion
            $table->boolean('allows_pricing_override')->default(true);
            $table->timestamps();

            $table->unique(['country_id', 'currency_id']);
            $table->index('is_primary');
        });

        // Languages table
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // en, de, fr, ja, etc.
            $table->string('name', 100);
            $table->string('native_name', 100)->nullable();
            $table->string('direction', 10)->default('ltr'); // ltr, rtl
            $table->string('flag_emoji', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
        });

        // Country-Language relationship
        Schema::create('country_language', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_official')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['country_id', 'language_id']);
        });

        // Tax Classes - Different tax categories per country
        Schema::create('tax_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100); // Standard Rate, Reduced Rate, Zero Rate, etc.
            $table->string('code', 50); // STANDARD, REDUCED, ZERO, EXEMPT
            $table->decimal('rate', 5, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->json('applicable_categories')->nullable(); // Category IDs this applies to
            $table->json('applicable_product_types')->nullable(); // Physical, digital, services
            $table->boolean('is_compound')->default(false); // Tax on tax
            $table->boolean('is_shipping_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_id', 'code']);
            $table->index('is_active');
        });

        // Import Duty Rules by HS Code and Country
        Schema::create('import_duty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('hs_code_pattern', 20); // Can use wildcards: 8542.*, 854231*, etc.
            $table->decimal('duty_rate', 5, 2)->default(0.00);
            $table->decimal('vat_rate', 5, 2)->nullable(); // Override default VAT for imports
            $table->decimal('excise_rate', 5, 2)->default(0.00);
            $table->string('origin_country_preference', 2)->nullable(); // ISO code for preferential rates
            $table->decimal('preferential_rate', 5, 2)->nullable(); // FTA rate
            $table->text('notes')->nullable();
            $table->boolean('requires_certificate')->default(false);
            $table->json('required_certificates')->nullable(); // ['CE', 'FCC', 'RoHS']
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_id', 'hs_code_pattern']);
            $table->index('is_active');
        });

        // Localization Settings per Country
        Schema::create('country_localization', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('domain', 100)->nullable(); // de.neogiga.com, neogiga.de
            $table->string('path_prefix', 20)->nullable(); // /de/, /germany/
            $table->string('seo_title_prefix', 100)->nullable();
            $table->string('seo_title_suffix', 100)->nullable();
            $table->text('default_meta_description')->nullable();
            $table->json('hreflang_tags')->nullable(); // {'en': '/en/', 'de': '/de/'}
            $table->string('canonical_domain')->nullable();
            $table->boolean('auto_redirect')->default(true);
            $table->boolean('show_currency_selector')->default(true);
            $table->boolean('show_language_selector')->default(true);
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('time_format', 20)->default('H:i:s');
            $table->string('number_format_decimal', 5)->default('.');
            $table->string('number_format_thousands', 5)->default(',');
            $table->string('address_format')->nullable(); // Template for address display
            $table->json('payment_methods')->nullable(); // Available payment methods
            $table->json('shipping_methods')->nullable(); // Available shipping methods
            $table->decimal('free_shipping_threshold', 15, 2)->nullable();
            $table->string('customer_support_email')->nullable();
            $table->string('customer_support_phone')->nullable();
            $table->json('business_hours')->nullable();
            $table->text('legal_notice')->nullable();
            $table->text('terms_url')->nullable();
            $table->text('privacy_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('country_id');
            $table->index('domain');
            $table->index('path_prefix');
        });

        // Price Lists per Country/Currency
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100); // "Germany EUR Retail", "USA USD B2B"
            $table->string('code', 50)->unique();
            $table->enum('type', ['retail', 'b2b', 'wholesale', 'contract', 'promotional']);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0); // Higher priority = selected first
            $table->json('customer_groups')->nullable(); // Applicable customer segments
            $table->json('seller_groups')->nullable(); // Applicable seller types
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_id', 'currency_id']);
            $table->index(['type', 'is_active']);
        });

        // Exchange Rate History
        Schema::create('exchange_rate_history', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->integer('rate')->comment('Rate multiplied by 100000 for precision');
            $table->date('effective_date');
            $table->string('source', 50)->nullable(); // ECB, Federal Reserve, etc.
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency', 'effective_date']);
            $table->index(['from_currency', 'to_currency']);
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_history');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('country_localization');
        Schema::dropIfExists('import_duty_rules');
        Schema::dropIfExists('tax_classes');
        Schema::dropIfExists('country_language');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('country_currency');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('countries');
    }
};
