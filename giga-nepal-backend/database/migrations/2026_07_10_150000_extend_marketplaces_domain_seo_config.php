<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Country-marketplace domain + SEO configuration system — additive only.
 * Every marketplaces column is hasColumn-guarded so this is safe to re-run and
 * cannot disturb the 26 existing rows or the live domains (neogiga.com/.in,
 * giganepal.com). Generated domains are NOT written here (a seeder does that,
 * preserving custom domains); columns simply default to a safe, unverified,
 * non-indexable, disabled state. See MARKETPLACE_DOMAIN_SEO_AUDIT.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplaces', function (Blueprint $table) {
            $add = function (string $name, callable $def) use ($table) {
                if (! Schema::hasColumn('marketplaces', $name)) {
                    $def($table);
                }
            };

            // Denormalized country/currency (backfilled by seeder from relations)
            $add('country_iso2', fn ($t) => $t->string('country_iso2', 2)->nullable()->index());
            $add('country_iso3', fn ($t) => $t->string('country_iso3', 3)->nullable());
            $add('currency_code', fn ($t) => $t->string('currency_code', 3)->nullable());
            $add('currency_symbol', fn ($t) => $t->string('currency_symbol', 8)->nullable());

            // Domain configuration
            $add('domain', fn ($t) => $t->string('domain')->nullable());
            $add('domain_mode', fn ($t) => $t->string('domain_mode', 20)->default('subdomain')); // custom_domain|subdomain|path
            $add('domain_prefix', fn ($t) => $t->string('domain_prefix', 20)->nullable());
            $add('generated_domain', fn ($t) => $t->string('generated_domain')->nullable());
            $add('canonical_domain', fn ($t) => $t->string('canonical_domain')->nullable());
            $add('force_https', fn ($t) => $t->boolean('force_https')->default(true));
            $add('redirect_to_canonical', fn ($t) => $t->boolean('redirect_to_canonical')->default(true));
            $add('www_redirect_mode', fn ($t) => $t->string('www_redirect_mode', 20)->default('none')); // none|www_to_non_www|non_www_to_www
            $add('domain_verified_at', fn ($t) => $t->timestamp('domain_verified_at')->nullable());
            $add('ssl_status', fn ($t) => $t->string('ssl_status', 20)->default('pending')); // pending|active|failed|not_required
            $add('is_domain_locked', fn ($t) => $t->boolean('is_domain_locked')->default(false));

            // Status / access (allow_checkout maps to existing checkout_enabled)
            $add('is_visible', fn ($t) => $t->boolean('is_visible')->default(false));
            $add('allow_customer_registration', fn ($t) => $t->boolean('allow_customer_registration')->default(false));
            $add('maintenance_mode', fn ($t) => $t->boolean('maintenance_mode')->default(false));
            $add('maintenance_message', fn ($t) => $t->text('maintenance_message')->nullable());
            $add('launch_at', fn ($t) => $t->timestamp('launch_at')->nullable());
            $add('disabled_at', fn ($t) => $t->timestamp('disabled_at')->nullable());
            $add('disabled_reason', fn ($t) => $t->text('disabled_reason')->nullable());

            // SEO
            $add('seo_title', fn ($t) => $t->string('seo_title')->nullable());
            $add('seo_description', fn ($t) => $t->text('seo_description')->nullable());
            $add('seo_keywords', fn ($t) => $t->text('seo_keywords')->nullable());
            $add('seo_h1', fn ($t) => $t->string('seo_h1')->nullable());
            $add('seo_canonical_url', fn ($t) => $t->string('seo_canonical_url')->nullable());
            $add('seo_robots', fn ($t) => $t->string('seo_robots', 40)->default('noindex,nofollow'));
            $add('seo_og_title', fn ($t) => $t->string('seo_og_title')->nullable());
            $add('seo_og_description', fn ($t) => $t->text('seo_og_description')->nullable());
            $add('seo_og_image', fn ($t) => $t->string('seo_og_image')->nullable());
            $add('seo_twitter_title', fn ($t) => $t->string('seo_twitter_title')->nullable());
            $add('seo_twitter_description', fn ($t) => $t->text('seo_twitter_description')->nullable());
            $add('seo_twitter_image', fn ($t) => $t->string('seo_twitter_image')->nullable());
            $add('seo_schema_json', fn ($t) => $t->json('seo_schema_json')->nullable());
            $add('seo_header_scripts', fn ($t) => $t->text('seo_header_scripts')->nullable());
            $add('seo_footer_scripts', fn ($t) => $t->text('seo_footer_scripts')->nullable());
            $add('sitemap_enabled', fn ($t) => $t->boolean('sitemap_enabled')->default(true));
            $add('hreflang_enabled', fn ($t) => $t->boolean('hreflang_enabled')->default(true));
            $add('indexable', fn ($t) => $t->boolean('indexable')->default(false));
            $add('seo_is_auto_generated', fn ($t) => $t->boolean('seo_is_auto_generated')->default(false));
            $add('seo_last_generated_at', fn ($t) => $t->timestamp('seo_last_generated_at')->nullable());
            $add('seo_manual_override_fields', fn ($t) => $t->json('seo_manual_override_fields')->nullable());

            // Content / branding
            $add('short_description', fn ($t) => $t->string('short_description')->nullable());
            $add('marketplace_description', fn ($t) => $t->text('marketplace_description')->nullable());
            $add('homepage_heading', fn ($t) => $t->string('homepage_heading')->nullable());
            $add('homepage_subheading', fn ($t) => $t->string('homepage_subheading')->nullable());
            $add('logo', fn ($t) => $t->string('logo')->nullable());
            $add('favicon', fn ($t) => $t->string('favicon')->nullable());
            $add('banner_image', fn ($t) => $t->string('banner_image')->nullable());

            // System
            $add('created_by', fn ($t) => $t->unsignedBigInteger('created_by')->nullable());
            $add('updated_by', fn ($t) => $t->unsignedBigInteger('updated_by')->nullable());
            $add('deleted_at', fn ($t) => $t->softDeletes());
        });

        // Indexes (guarded via try/catch — index existence isn't introspectable portably)
        Schema::table('marketplaces', function (Blueprint $table) {
            try {
                $table->index(['is_active', 'is_visible'], 'marketplaces_active_visible_idx');
            } catch (\Throwable) {
            }
            try {
                $table->index(['generated_domain'], 'marketplaces_generated_domain_idx');
            } catch (\Throwable) {
            }
            try {
                $table->index(['canonical_domain'], 'marketplaces_canonical_domain_idx');
            } catch (\Throwable) {
            }
        });

        // Extend marketplace_domains
        if (Schema::hasTable('marketplace_domains')) {
            Schema::table('marketplace_domains', function (Blueprint $table) {
                if (! Schema::hasColumn('marketplace_domains', 'domain_type')) {
                    $table->string('domain_type', 20)->default('primary'); // primary|alias|redirect|development
                }
                if (! Schema::hasColumn('marketplace_domains', 'redirect_url')) {
                    $table->string('redirect_url')->nullable();
                }
                if (! Schema::hasColumn('marketplace_domains', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable();
                }
                if (! Schema::hasColumn('marketplace_domains', 'ssl_status')) {
                    $table->string('ssl_status', 20)->default('pending');
                }
            });
        }

        // Audit log
        if (! Schema::hasTable('marketplace_audit_logs')) {
            Schema::create('marketplace_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 80);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                $table->index(['marketplace_id', 'action']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_audit_logs');

        if (Schema::hasTable('marketplace_domains')) {
            Schema::table('marketplace_domains', function (Blueprint $table) {
                foreach (['domain_type', 'redirect_url', 'verified_at', 'ssl_status'] as $c) {
                    if (Schema::hasColumn('marketplace_domains', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }

        $cols = [
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
            'short_description', 'marketplace_description', 'homepage_heading', 'homepage_subheading',
            'logo', 'favicon', 'banner_image', 'created_by', 'updated_by', 'deleted_at',
        ];
        Schema::table('marketplaces', function (Blueprint $table) use ($cols) {
            foreach ($cols as $c) {
                if (Schema::hasColumn('marketplaces', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
