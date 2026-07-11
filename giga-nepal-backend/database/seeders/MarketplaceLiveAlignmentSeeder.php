<?php

namespace Database\Seeders;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceAuditLog;
use Illuminate\Database\Seeder;

/**
 * Aligns the genuinely-live custom-domain marketplaces (neogiga.com/.in,
 * giganepal.com) so the new domain/SEO columns reflect reality: they are real,
 * DNS-configured, HTTPS-serving, indexed production sites.
 *
 * Scope is data-driven and narrow: only marketplaces that are already
 * is_domain_locked custom_domain rows (i.e. the production customs) are touched.
 * domain_verified_at + ssl_status='active' are set ONLY when a real HTTPS
 * request to the domain succeeds — a domain is never marked verified without a
 * real check. is_visible / indexable / robots reflect that these are public,
 * indexable sites. Idempotent; audited.
 */
class MarketplaceLiveAlignmentSeeder extends Seeder
{
    public function run(): void
    {
        Marketplace::query()
            ->where('domain_mode', 'custom_domain')
            ->where('is_domain_locked', true)
            ->whereNotNull('domain')
            ->get()
            ->each(function (Marketplace $m) {
                $old = [
                    'is_visible' => $m->is_visible,
                    'indexable' => $m->indexable,
                    'ssl_status' => $m->ssl_status,
                    'domain_verified_at' => (string) $m->domain_verified_at,
                ];

                $m->is_visible = true;
                $m->indexable = true;
                $m->seo_robots = 'index,follow';

                if ($this->httpsResponds($m->domain)) {
                    $m->ssl_status = 'active';
                    $m->domain_verified_at = $m->domain_verified_at ?: now();
                }

                $m->save();

                MarketplaceAuditLog::record(
                    $m->id,
                    'live_marketplace_aligned',
                    $old,
                    [
                        'is_visible' => true,
                        'indexable' => true,
                        'ssl_status' => $m->ssl_status,
                        'verified' => $m->domain_verified_at !== null,
                    ],
                );
            });
    }

    /** Real HTTPS check — a 2xx/3xx response counts as verified. Never throws. */
    private function httpsResponds(string $host): bool
    {
        try {
            $ctx = stream_context_create(['http' => ['method' => 'HEAD', 'timeout' => 4, 'ignore_errors' => true]]);
            $headers = @get_headers("https://{$host}/", true, $ctx);
            if (! $headers || ! isset($headers[0])) {
                return false;
            }

            return (bool) preg_match('/\s(2\d\d|3\d\d)\s/', $headers[0]);
        } catch (\Throwable) {
            return false;
        }
    }
}
