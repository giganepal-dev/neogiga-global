<?php

namespace Database\Seeders;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceAuditLog;
use App\Services\Marketplace\MarketplaceDomainService;
use App\Services\Marketplace\MarketplaceSeoService;
use Illuminate\Database\Seeder;

/**
 * Backfills domain + SEO configuration for existing marketplaces. Idempotent
 * and NON-destructive:
 *  - Custom production domains (neogiga.com/.in, giganepal.com) are set once and
 *    locked; never overwritten on re-run.
 *  - Every other marketplace gets a generated {iso2}.neogiga.com domain but stays
 *    INACTIVE with ssl_status 'pending' — a generated domain is not a live DNS.
 *  - SEO empty fields are auto-filled; manual overrides and non-empty fields are
 *    left alone. robots reflects real visibility (inactive ⇒ noindex).
 * Never activates a marketplace and never marks a domain verified.
 */
class MarketplaceDomainSeoSeeder extends Seeder
{
    /** code => canonical custom domain (production, locked). */
    private const CUSTOM = [
        'GLOBAL' => 'neogiga.com',
        'NEPAL' => 'giganepal.com',
        'INDIA' => 'neogiga.in',
    ];

    public function run(): void
    {
        $domains = app(MarketplaceDomainService::class);
        $seo = app(MarketplaceSeoService::class);

        Marketplace::query()->with(['country', 'currency'])->get()->each(function (Marketplace $m) use ($domains, $seo) {
            $code = strtoupper((string) $m->code);

            // Denormalized country/currency (safe backfill only)
            $m->country_iso2 = $m->country_iso2 ?: $m->country?->iso_code_2;
            $m->country_iso3 = $m->country_iso3 ?: $m->country?->iso_code_3;
            $m->currency_code = $m->currency_code ?: $m->currency?->code;
            $m->currency_symbol = $m->currency_symbol ?: $m->currency?->symbol;
            $m->domain_prefix = $m->domain_prefix ?: $m->url_prefix;

            if (isset(self::CUSTOM[$code])) {
                $custom = self::CUSTOM[$code];
                // Set once; preserve if already present.
                $m->domain = $m->domain ?: $custom;
                $m->domain_mode = 'custom_domain';
                $m->generated_domain = $m->generated_domain ?: $custom;
                $m->canonical_domain = $m->canonical_domain ?: $custom;
                $m->is_domain_locked = true; // production domain — protect from regenerate
                $m->save();
            } else {
                if (empty($m->domain_mode) || $m->domain_mode === 'custom_domain') {
                    $m->domain_mode = 'subdomain';
                }
                $m->save();
                $domains->backfillGeneratedDomain($m); // sets {iso2}.neogiga.com if empty
                $m->refresh();
                $m->canonical_domain = $m->canonical_domain ?: $m->generated_domain;
                $m->save();
            }

            // Auto-fill empty SEO; robots reflects current (likely inactive) state.
            $seo->apply($m, onlyEmpty: true);
        });

        MarketplaceAuditLog::record(
            null,
            'domain_seo_backfill_seeded',
            [],
            ['marketplaces' => Marketplace::count(), 'note' => 'generated domains + default SEO backfilled; no activation, no verification'],
        );
    }
}
