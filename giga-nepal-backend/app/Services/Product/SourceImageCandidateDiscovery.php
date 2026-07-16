<?php

namespace App\Services\Product;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Discovers image URLs from declared manufacturer/source pages without
 * downloading or publishing any asset. A separate rights-review workflow is
 * required before a candidate can be fetched into NeoGiga storage.
 */
class SourceImageCandidateDiscovery
{
    /**
     * @param  array{source_name?:string,manufacturer?:string,allowed_hosts:list<string>,limit?:int,timeout?:int,concurrency?:int,min_confidence?:float,apply?:bool}  $options
     * @return array<string, int>
     */
    public function discover(array $options, ?callable $progress = null): array
    {
        $allowedHosts = $this->normalizedHosts($options['allowed_hosts'] ?? []);
        if ($allowedHosts === []) {
            throw new \InvalidArgumentException('At least one allowed source host is required.');
        }

        $sourceName = trim((string) ($options['source_name'] ?? ''));
        $manufacturer = trim((string) ($options['manufacturer'] ?? ''));
        if ($sourceName === '' && $manufacturer === '') {
            throw new \InvalidArgumentException('A source name or manufacturer selector is required.');
        }

        $limit = max(0, (int) ($options['limit'] ?? 0));
        $timeout = max(2, min(30, (int) ($options['timeout'] ?? 10)));
        $concurrency = max(1, min(10, (int) ($options['concurrency'] ?? 4)));
        $minConfidence = max(0.0, min(1.0, (float) ($options['min_confidence'] ?? 0.70)));
        $apply = (bool) ($options['apply'] ?? false);
        $stats = [
            'products_seen' => 0,
            'source_rejected' => 0,
            'pages_fetched' => 0,
            'fetch_failed' => 0,
            'without_candidate' => 0,
            'candidates_found' => 0,
            'candidates_stored' => 0,
        ];
        $lastId = 0;

        while (true) {
            $remaining = $limit > 0 ? $limit - $stats['products_seen'] : $concurrency;
            if ($remaining <= 0) {
                break;
            }
            $take = min($concurrency, $remaining);
            $products = DB::table('products as p')
                ->where('p.id', '>', $lastId)
                ->whereNotNull('p.source_page_url')
                ->where('p.source_page_url', '<>', '')
                ->when($sourceName !== '', fn ($query) => $query->where('p.source_name', $sourceName))
                ->when($manufacturer !== '', fn ($query) => $query->whereRaw('LOWER(COALESCE(p.manufacturer_name, \'\')) = ?', [mb_strtolower($manufacturer)]))
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('product_images as pi')
                        ->whereColumn('pi.product_id', 'p.id')
                        ->where('pi.is_active', true);
                })
                ->orderBy('p.id')
                ->limit($take)
                ->get([
                    'p.id', 'p.mpn', 'p.manufacturer_name', 'p.source_name', 'p.source_url', 'p.source_file',
                    'p.source_page_url', 'p.data_year', 'p.license_note', 'p.confidence_level',
                ]);

            if ($products->isEmpty()) {
                break;
            }
            $lastId = (int) $products->last()->id;
            $eligible = $products->filter(fn ($product) => $this->matchesAllowedHost((string) $product->source_page_url, $allowedHosts));
            $stats['products_seen'] += $products->count();
            $stats['source_rejected'] += $products->count() - $eligible->count();

            if ($eligible->isEmpty()) {
                if ($progress) {
                    $progress($stats);
                }

                continue;
            }

            try {
                $responses = Http::pool(function (Pool $pool) use ($eligible, $timeout) {
                    return $eligible->map(function ($product) use ($pool, $timeout) {
                        return $pool->as((string) $product->id)
                            ->accept('text/html,application/xhtml+xml')
                            ->withUserAgent('NeoGigaImageCandidateBot/1.0 (+https://neogiga.com)')
                            ->retry(1, 250)
                            ->timeout($timeout)
                            ->get((string) $product->source_page_url);
                    })->all();
                });
            } catch (Throwable) {
                $stats['fetch_failed'] += $eligible->count();
                if ($progress) {
                    $progress($stats);
                }

                continue;
            }

            $pending = [];
            foreach ($eligible as $product) {
                $response = $responses[(string) $product->id] ?? null;
                if (! $response instanceof Response || ! $response->ok() || ! $this->isHtml($response)) {
                    $stats['fetch_failed']++;

                    continue;
                }

                $stats['pages_fetched']++;
                $checksum = hash('sha256', (string) $response->body());
                $candidates = array_filter(
                    $this->extractCandidates((string) $response->body(), (string) $product->source_page_url, (string) $product->mpn),
                    fn (array $candidate): bool => $candidate['confidence'] >= $minConfidence
                        && $this->matchesAllowedHost($candidate['url'], $allowedHosts),
                );
                if ($candidates === []) {
                    $stats['without_candidate']++;

                    continue;
                }

                foreach ($candidates as $candidate) {
                    $pending[] = $this->candidatePayload($product, $candidate, $checksum);
                    $stats['candidates_found']++;
                }
            }

            if ($apply && $pending !== []) {
                DB::table('product_image_candidates')->upsert(
                    $pending,
                    ['product_id', 'candidate_url'],
                    [
                        'source_page_url', 'source_name', 'manufacturer', 'mpn', 'discovered_by', 'confidence_score',
                        'evidence', 'source_url', 'source_file', 'source_part_id', 'downloaded_at', 'imported_at',
                        'data_year', 'license_note', 'confidence_level', 'original_raw_value', 'normalized_value',
                        'source_checksum', 'rights_basis', 'rights_review_required', 'is_active', 'image_role',
                        'asset_fetch_status', 'updated_at',
                    ],
                );
                $stats['candidates_stored'] += count($pending);
            }
            if ($progress) {
                $progress($stats);
            }
        }

        return $stats;
    }

    /** @return list<array{url:string,selector:string,matched_mpn:bool,confidence:float}> */
    public function extractCandidates(string $html, string $baseUrl, string $mpn): array
    {
        $candidates = [];
        $mpnNeedle = $this->normalizedMpn($mpn);
        $add = function (mixed $url, string $selector, bool $matchedMpn = false, string $context = '') use (&$candidates, $baseUrl, $mpnNeedle): void {
            $resolved = $this->resolveImageUrl((string) $url, $baseUrl);
            if ($resolved === null) {
                return;
            }
            $contextMatch = $mpnNeedle !== '' && str_contains($this->normalizedMpn($context.$resolved), $mpnNeedle);
            $confidence = match (true) {
                str_starts_with($selector, 'json_ld') => 0.85,
                $selector === 'meta' => 0.75,
                default => 0.55,
            };
            if ($matchedMpn || $contextMatch) {
                $confidence += 0.10;
            }
            $candidates[$resolved] = [
                'url' => $resolved,
                'selector' => $selector,
                'matched_mpn' => $matchedMpn || $contextMatch,
                'confidence' => min(0.95, $confidence),
            ];
        };

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//meta[@property="og:image" or @property="og:image:secure_url" or @name="twitter:image"]') ?: [] as $node) {
            $add($node->attributes?->getNamedItem('content')?->nodeValue, 'meta');
        }
        foreach ($xpath->query('//img[@src or @data-src]') ?: [] as $node) {
            $src = $node->attributes?->getNamedItem('src')?->nodeValue ?: $node->attributes?->getNamedItem('data-src')?->nodeValue;
            $context = implode(' ', [
                $node->attributes?->getNamedItem('alt')?->nodeValue ?? '',
                $node->attributes?->getNamedItem('class')?->nodeValue ?? '',
                $node->attributes?->getNamedItem('data-mpn')?->nodeValue ?? '',
            ]);
            $add($src, 'img', false, $context);
        }
        foreach ($xpath->query('//script[@type="application/ld+json"]') ?: [] as $node) {
            $script = (string) $node->textContent;
            $decoded = json_decode($script, true);
            if (is_array($decoded)) {
                $this->collectJsonImages($decoded, $mpnNeedle, $add);

                continue;
            }

            // Some official pages expose JSON-LD with unescaped control
            // characters. Capture only the first product-image field from the
            // source page rather than attempting to repair or retain the data.
            if (preg_match('/"(?:distModalImage|imageUrl|image)"\\s*:\\s*"((?:\\\\.|[^"\\\\])+)"/i', $script, $matches)) {
                $url = json_decode('"'.$matches[1].'"') ?: stripcslashes($matches[1]);
                $add($url, 'json_ld_fallback', true, $mpn);
            }
        }

        return array_values($candidates);
    }

    /** @param array<mixed> $node */
    private function collectJsonImages(array $node, string $mpnNeedle, callable $add, bool $ancestorMatchesMpn = false): void
    {
        $identity = implode(' ', array_filter([
            is_scalar($node['mpn'] ?? null) ? (string) $node['mpn'] : null,
            is_scalar($node['sku'] ?? null) ? (string) $node['sku'] : null,
            is_scalar($node['partNumber'] ?? null) ? (string) $node['partNumber'] : null,
        ]));
        $matchesMpn = $ancestorMatchesMpn || ($mpnNeedle !== '' && str_contains($this->normalizedMpn($identity), $mpnNeedle));
        foreach (['image', 'imageUrl', 'distModalImage', 'contentUrl'] as $field) {
            if (! array_key_exists($field, $node)) {
                continue;
            }
            $value = $node[$field];
            if (is_scalar($value)) {
                $add((string) $value, 'json_ld', $matchesMpn, $identity);
            } elseif (is_array($value)) {
                foreach ($value as $image) {
                    if (is_scalar($image)) {
                        $add((string) $image, 'json_ld', $matchesMpn, $identity);
                    } elseif (is_array($image)) {
                        $add($image['url'] ?? $image['contentUrl'] ?? null, 'json_ld', $matchesMpn, $identity);
                    }
                }
            }
        }
        foreach ($node as $value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $this->collectJsonImages($item, $mpnNeedle, $add, $matchesMpn);
                        }
                    }
                } else {
                    $this->collectJsonImages($value, $mpnNeedle, $add, $matchesMpn);
                }
            }
        }
    }

    private function resolveImageUrl(string $url, string $baseUrl): ?string
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, 'data:')) {
            return null;
        }
        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        } elseif (str_starts_with($url, '/')) {
            $parts = parse_url($baseUrl);
            $url = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').$url;
        } elseif (! preg_match('#^https?://#i', $url)) {
            $url = rtrim(dirname($baseUrl), '/').'/'.$url;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('/\.(?:jpe?g|png|webp|avif|gif)(?:[?#:]|$)/i', $url)) {
            return null;
        }
        if (preg_match('#/(?:icons?|logos?)/|no-image-available#i', $url)) {
            return null;
        }

        return $url;
    }

    /** @param list<string> $allowedHosts */
    private function matchesAllowedHost(string $url, array $allowedHosts): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        foreach ($allowedHosts as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.'.$allowedHost)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $hosts @return list<string> */
    private function normalizedHosts(array $hosts): array
    {
        return array_values(array_unique(array_filter(array_map(function (string $host): string {
            $host = strtolower(trim($host));
            $host = preg_replace('#^https?://#', '', $host) ?? $host;

            return trim(explode('/', $host)[0]);
        }, $hosts))));
    }

    private function isHtml(Response $response): bool
    {
        $contentType = strtolower((string) $response->header('content-type'));

        return str_contains($contentType, 'text/html') || str_contains($contentType, 'application/xhtml+xml');
    }

    private function normalizedMpn(string $value): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]+/i', '', $value));
    }

    private function candidatePayload(object $product, array $candidate, string $sourceChecksum): array
    {
        $now = now();

        return [
            'product_id' => $product->id,
            'candidate_url' => $candidate['url'],
            'source_page_url' => $product->source_page_url,
            'source_name' => $product->source_name,
            'manufacturer' => $product->manufacturer_name,
            'mpn' => $product->mpn,
            'discovered_by' => 'product-images:discover-source-candidates',
            'rights_status' => 'pending_review',
            'confidence_score' => $candidate['confidence'],
            'evidence' => json_encode([
                'selector' => $candidate['selector'],
                'matched_mpn' => $candidate['matched_mpn'],
                'advisory' => 'Official-source candidate URL only. Downloading and publishing remain blocked until redistribution rights are approved.',
            ]),
            'source_url' => $product->source_url ?: $product->source_page_url,
            'source_file' => $product->source_file,
            'source_part_id' => $product->mpn,
            'downloaded_at' => $now,
            'imported_at' => $now,
            'data_year' => $product->data_year ?: (int) $now->year,
            'license_note' => $product->license_note ?: 'Source-page image candidate only; redistribution rights require separate approval.',
            'confidence_level' => $product->confidence_level ?: 'source_page_candidate',
            'original_raw_value' => json_encode(['candidate_url' => $candidate['url'], 'source_page_url' => $product->source_page_url]),
            'normalized_value' => $candidate['url'],
            'source_checksum' => $sourceChecksum,
            'rights_basis' => 'Discovered from a declared official source page. No asset is downloaded, cached, or published by this job.',
            'rights_review_required' => true,
            'is_active' => false,
            'image_role' => 'product',
            'asset_fetch_status' => 'not_requested',
            'discovered_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
