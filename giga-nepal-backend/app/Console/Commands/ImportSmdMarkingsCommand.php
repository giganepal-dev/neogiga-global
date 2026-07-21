<?php

namespace App\Console\Commands;

use App\Services\Smd\SmdMarkingNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportSmdMarkingsCommand extends Command
{
    protected $signature = 'neogiga:smd-import
                            {--source=yooneed : Source identifier}
                            {--limit=0 : Max pages to process (0=all)}
                            {--offset=0 : Start from this page index}
                            {--dry-run : Discover only, no writes}
                            {--resume : Skip pages with matching content hash}
                            {--delay=2 : Seconds between requests}
                            {--concurrency=1 : Concurrent requests}
                            {--prefix= : Process only this prefix page (e.g. code3031)}';

    protected $description = 'Import SMD marking codes from yooneed.one into the identification database.';

    private const BASE_URL = 'https://smd.yooneed.one/';
    private const USER_AGENT = 'NeoGigaCatalogResearch/1.0 (+https://neogiga.com)';
    private const SOURCE = 'yooneed';

    private SmdMarkingNormalizer $normalizer;
    private array $stats = [
        'pages_discovered' => 0,
        'pages_processed' => 0,
        'pages_skipped' => 0,
        'rows_extracted' => 0,
        'markings_created' => 0,
        'matches_created' => 0,
        'matches_updated' => 0,
        'duplicates_skipped' => 0,
        'parse_failures' => 0,
        'products_matched' => 0,
    ];

    public function handle(): int
    {
        $this->normalizer = new SmdMarkingNormalizer();

        if ($prefix = $this->option('prefix')) {
            $pages = [self::BASE_URL . $prefix . '.html'];
        } else {
            $pages = $this->discoverPrefixPages();
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run — {$this->stats['pages_discovered']} pages discovered. No writes.");

            return self::SUCCESS;
        }

        $this->info("Processing {$this->stats['pages_discovered']} pages...");
        $bar = $this->output->createProgressBar($this->stats['pages_discovered']);

        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');
        $processed = 0;

        foreach ($pages as $i => $url) {
            if ($i < $offset) {
                continue;
            }
            if ($limit > 0 && $processed >= $limit) {
                $this->stats['pages_skipped'] = $this->stats['pages_discovered'] - $processed - $offset;
                break;
            }

            $this->processPage($url);
            $processed++;
            $bar->advance();

            if ($delay > 0) {
                usleep($delay * 1000 * 1000 + random_int(0, 500) * 1000); // +0-500ms jitter
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->printStats();

        return self::SUCCESS;
    }

    private function discoverPrefixPages(): array
    {
        $this->info('Discovering prefix pages from index...');
        $indexHtml = $this->fetch(self::BASE_URL);

        if (! $indexHtml) {
            $this->error('Failed to fetch index page.');

            return [];
        }

        // Extract all codeXXXX.html links
        preg_match_all('/href="(code[A-Za-z0-9_\.\-]+\.html)"/', $indexHtml, $matches);
        $pages = array_unique($matches[1]);
        $urls = array_map(fn ($p) => self::BASE_URL . $p, $pages);
        sort($urls);

        $this->stats['pages_discovered'] = count($urls);
        $this->info("Found {$this->stats['pages_discovered']} prefix pages.");

        return $urls;
    }

    private function processPage(string $url): void
    {
        $html = $this->fetch($url);
        if (! $html) {
            $this->stats['parse_failures']++;

            return;
        }

        $contentHash = hash('sha256', $html);
        $pageName = basename($url, '.html');

        // Resume: skip if content unchanged
        if ($this->option('resume')) {
            $existing = DB::table('smd_marking_matches')
                ->where('source_url', $url)
                ->where('source_hash', $contentHash)
                ->exists();
            if ($existing) {
                $this->stats['pages_skipped']++;

                return;
            }
        }

        $rows = $this->parseComponentRows($html, $url);
        $this->stats['pages_processed']++;

        foreach ($rows as $row) {
            $this->storeRow($row, $url, $contentHash);
        }
    }

    private function parseComponentRows(string $html, string $sourceUrl): array
    {
        $rows = [];

        // Extract the results-contents div (DIV-based table, not HTML table)
        if (! preg_match('/<div class="results-contents">(.*?)<\/div>\s*<\/div>\s*<\/div>/si', $html, $container)) {
            return $rows;
        }

        // Each row: <div class="rr ..." data-row="N"><div>MARKING</div><div>MPN</div><div class="mc">MFR<br>PACKAGE<img></div><div>FUNCTION</div></div>
        preg_match_all(
            '/<div class="rr[^"]*"[^>]*>\s*<div>(.*?)<\/div>\s*<div>(.*?)<\/div>\s*<div[^>]*>(.*?)<\/div>\s*<div>(.*?)<\/div>\s*<\/div>/si',
            $container[1],
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as $i => $m) {
            $marking = trim(strip_tags($m[1]));
            $mpn = trim(strip_tags($m[2]));
            $mfrPkg = trim(strip_tags($m[3]));
            $function = trim(strip_tags($m[4]));
            $function = html_entity_decode($function, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Skip header row or empty rows
            if ($marking === 'Marking' || empty($marking) || empty($mpn)) {
                continue;
            }

            // Extract package image URL if present
            $pkgImageUrl = '';
            if (preg_match('/<img[^>]+src="([^"]+)"/', $m[3], $imgMatch)) {
                $pkgImageUrl = $imgMatch[1];
            }

            // Parse "Manufacturer<br>Package<img>" field
            $mfrPkgClean = strip_tags($mfrPkg, '<br>'); // keep <br> for splitting
            $mfrPkgParts = preg_split('/<br\s*\/?>/i', $mfrPkgClean);
            $manufacturer = trim($mfrPkgParts[0] ?? '');
            $packageText = trim($mfrPkgParts[1] ?? '');

            // Split "Manufacturer / Case" as fallback
            if (empty($packageText) && str_contains($manufacturer, '/')) {
                $parts = array_map('trim', explode('/', $manufacturer, 2));
                $manufacturer = $parts[0] ?? '';
                $packageText = $parts[1] ?? '';
            }

            $rows[] = [
                'source_position' => $i + 1,
                'marking' => $marking,
                'mpn' => $mpn,
                'manufacturer' => $manufacturer,
                'package_text' => $packageText,
                'package_image_url' => $pkgImageUrl,
                'function' => $function,
            ];
        }

        $this->stats['rows_extracted'] += count($rows);

        return $rows;
    }

    private function storeRow(array $row, string $sourceUrl, string $contentHash): void
    {
        $displayMarking = $this->normalizer->display($row['marking']);
        $normalizedMarking = $this->normalizer->normalize($row['marking']);
        $normalizedMpn = $this->normalizer->normalize($row['mpn']);

        if (empty($normalizedMarking) || empty($normalizedMpn)) {
            $this->stats['parse_failures']++;

            return;
        }

        // Find or create marking code
        $codeId = DB::table('smd_marking_codes')->where('normalized_marking', $normalizedMarking)
            ->where('source_id', null)
            ->value('id');

        if (! $codeId) {
            $codeId = DB::table('smd_marking_codes')->insertGetId([
                'normalized_marking' => $normalizedMarking,
                'display_marking' => $displayMarking,
                'marking_length' => mb_strlen($normalizedMarking),
                'first_character' => mb_substr($normalizedMarking, 0, 1),
                'first_two_characters' => mb_substr($normalizedMarking, 0, 2),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->stats['markings_created']++;
        }

        // Find or create package
        $packageId = null;
        if (! empty($row['package_text'])) {
            $pkgName = $this->normalizer->normalize($row['package_text']);
            $packageId = DB::table('smd_packages')
                ->where('normalized_name', $pkgName)
                ->orWhereJsonContains('aliases', $row['package_text'])
                ->value('id');
        }

        // Find manufacturer
        $manufacturerId = null;
        if (! empty($row['manufacturer'])) {
            $manufacturerId = DB::table('manufacturers')
                ->where('name', $row['manufacturer'])
                ->orWhere('name', 'ILIKE', '%' . $row['manufacturer'] . '%')
                ->value('id');
        }

        // Try to match existing NeoGiga product
        $productId = null;
        if ($manufacturerId && $normalizedMpn) {
            $productId = DB::table('products')
                ->where('manufacturer_id', $manufacturerId)
                ->where(function ($q) use ($row, $normalizedMpn) {
                    $q->where('mpn', $normalizedMpn)
                      ->orWhere('sku', $normalizedMpn)
                      ->orWhere('name', 'ILIKE', '%' . $row['mpn'] . '%');
                })
                ->value('id');
        }

        // Check duplicate by unique constraint
        $duplicateKey = [
            'smd_marking_code_id' => $codeId,
            'normalized_mpn' => $normalizedMpn,
            'manufacturer_id' => $manufacturerId,
            'source_hash' => $contentHash,
        ];

        $exists = DB::table('smd_marking_matches')->where($duplicateKey)->exists();

        if ($exists) {
            $this->stats['duplicates_skipped']++;

            return;
        }

        // Calculate initial confidence
        $confidence = 30; // base: exact marking match
        if ($productId) {
            $confidence += 10; // verified NeoGiga product boost
        }

        DB::table('smd_marking_matches')->insert([
            'smd_marking_code_id' => $codeId,
            'product_id' => $productId,
            'manufacturer_id' => $manufacturerId,
            'candidate_mpn' => $row['mpn'],
            'normalized_mpn' => $normalizedMpn,
            'package_id' => $packageId,
            'package_text' => $row['package_text'],
            'component_function' => $row['function'],
            'characteristic_text' => null, // yooneed combines function+characteristics
            'match_confidence' => $confidence,
            'confidence_factors' => json_encode(['exact_marking' => 30, 'verified_product' => $productId ? 10 : 0]),
            'verification_status' => $productId ? 'matched' : 'unverified',
            'source_url' => $sourceUrl . '#pos' . $row['source_position'],
            'source_hash' => $contentHash,
            'retrieved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->stats['matches_created']++;
        if ($productId) {
            $this->stats['products_matched']++;
        }
    }

    private function fetch(string $url): ?string
    {
        $retries = 3;
        for ($attempt = 0; $attempt < $retries; $attempt++) {
            try {
                $response = Http::withUserAgent(self::USER_AGENT)
                    ->timeout(30)
                    ->connectTimeout(10)
                    ->get($url);

                if ($response->successful()) {
                    return $response->body();
                }

                if ($response->status() === 404 || $response->status() === 403) {
                    return null;
                }

                // Rate limited or server error — back off
                if ($attempt < $retries - 1) {
                    sleep((int) pow(2, $attempt + 1)); // 2, 4, 8 seconds
                }
            } catch (\Exception $e) {
                if ($attempt < $retries - 1) {
                    sleep((int) pow(2, $attempt + 1));
                }
            }
        }

        return null;
    }

    private function printStats(): void
    {
        $this->info('=== Import Statistics ===');
        foreach ($this->stats as $key => $val) {
            $label = str_replace('_', ' ', $key);
            $this->line("  <info>{$label}</info>: {$val}");
        }
    }
}
