<?php

namespace App\Catalog\Ingestion\Validation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupplierPolicyService
{
    /** @return array<string, mixed> */
    public function audit(string $supplier, bool $persist = true): array
    {
        $definition = $this->definition($supplier);
        $response = null;
        $transportError = null;
        try {
            $response = Http::accept('text/plain')->withUserAgent($this->userAgent())
                ->timeout((int) config('catalog_import.timeout'))
                ->connectTimeout((int) config('catalog_import.connect_timeout'))
                ->retry((int) config('catalog_import.retry_attempts'), 500, throw: false)
                ->get($definition['robots_url']);
        } catch (\Throwable $exception) {
            $transportError = $exception->getMessage();
        }

        $body = $response?->successful() ? $response->body() : '';
        $disallowsAll = (bool) preg_match('/User-agent:\s*\*.*?Disallow:\s*\/\s*$/ims', $body);
        $sitemaps = [];
        preg_match_all('/^Sitemap:\s*(\S+)\s*$/im', $body, $matches);
        foreach ($matches[1] ?? [] as $url) {
            $sitemaps[] = trim($url);
        }
        $result = [
            'supplier' => $supplier,
            'robots_url' => $definition['robots_url'],
            'terms_url' => $definition['terms_url'],
            'robots_http_status' => $response?->status() ?? 0,
            'robots_retrieved_at' => now()->toIso8601String(),
            'robots_disallows_all' => $disallowsAll,
            'sitemaps' => array_values(array_unique($sitemaps)),
            'policy_status' => $response?->successful() && ! $disallowsAll ? 'pending_manual_review' : 'blocked',
            'import_allowed' => false,
            'reason' => $transportError ? 'Transport or TLS verification failed; the source remains blocked.' : 'A human must confirm terms, robots scope, and redistribution rights before imports are enabled.',
        ];

        if ($persist) {
            $sourceId = $this->upsertSource($supplier, $definition, $result);
            DB::table('supplier_sources')->updateOrInsert([
                'catalog_source_id' => $sourceId,
                'source_url' => $definition['robots_url'],
            ], [
                'name' => 'robots.txt', 'source_kind' => 'robots', 'priority' => 1,
                'parser_class' => null, 'enabled' => false, 'configuration_json' => json_encode($result),
                'updated_at' => now(), 'created_at' => now(),
            ]);
        }

        return $result;
    }

    public function assertImportAllowed(string $supplier): array
    {
        $definition = $this->definition($supplier);
        if (! config('catalog_import.enabled') || empty($definition['enabled'])) {
            throw new \RuntimeException('Catalogue importing is disabled by configuration. Enable only after policy approval.');
        }
        $source = DB::table('catalog_sources')->where('code', $supplier)->first();
        if (! $source || ! $source->import_enabled || $source->status !== 'approved') {
            throw new \RuntimeException('Supplier policy is not approved for import. Run catalog:supplier-audit and complete manual review first.');
        }

        return $definition + ['user_agent' => $this->userAgent()];
    }

    /** @return array<string, mixed> */
    public function definition(string $supplier): array
    {
        $definition = config("catalog_import.suppliers.{$supplier}");
        if (! is_array($definition)) {
            throw new \InvalidArgumentException("Unsupported supplier [{$supplier}].");
        }

        return $definition + ['code' => $supplier, 'user_agent' => $this->userAgent()];
    }

    private function upsertSource(string $supplier, array $definition, array $policy): int
    {
        DB::table('catalog_sources')->updateOrInsert(['code' => $supplier], [
            'name' => $definition['name'], 'source_url' => $definition['base_url'], 'license_notes' => 'Rights not confirmed. Supplier content remains pending review.',
            'active' => true, 'source_type' => 'supplier', 'base_url' => $definition['base_url'], 'country_code' => $definition['country_code'],
            'terms_url' => $definition['terms_url'], 'robots_url' => $definition['robots_url'], 'catalogue_policy' => json_encode($policy),
            'user_agent' => $this->userAgent(), 'crawl_delay_ms' => max(1000, (int) floor(60000 / max(1, $definition['rpm'] ?: config('catalog_import.default_rpm')))),
            'maximum_requests_per_minute' => $definition['rpm'] ?: config('catalog_import.default_rpm'), 'import_enabled' => false,
            'media_download_enabled' => false, 'description_reuse_status' => $definition['description_reuse_status'],
            'status' => $policy['policy_status'], 'updated_at' => now(), 'created_at' => now(),
        ]);

        return (int) DB::table('catalog_sources')->where('code', $supplier)->value('id');
    }

    private function userAgent(): string
    {
        $agent = (string) config('catalog_import.user_agent');
        $contact = trim((string) config('catalog_import.contact'));

        return $contact && ! Str::contains($agent, $contact) ? "{$agent} ({$contact})" : $agent;
    }
}
