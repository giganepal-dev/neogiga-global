<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Marketing Phase 2 scheduler. Jobs aggregate first-party data and keep external providers disabled until explicitly configured.
use App\Jobs\Marketing\CalculateTopSearchTermsJob;
use App\Jobs\Marketing\CalculateTrendingCategoriesJob;
use App\Jobs\Marketing\CalculateTrendingProductsJob;
use App\Jobs\Marketing\DetectAbandonedCartsJob;
use App\Jobs\Marketing\GenerateRegionalSalesReportJob;
use App\Jobs\Marketing\RefreshCustomerSegmentJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Artisan::command('neogiga:smoke', function () {
    $failed = false;

    $check = function (string $name, callable $callback) use (&$failed): void {
        try {
            $result = (bool) $callback();
            $this->line(($result ? 'PASS' : 'FAIL') . ' ' . $name);
            $failed = $failed || ! $result;
        } catch (Throwable $exception) {
            $this->line('FAIL ' . $name . ' - ' . $exception->getMessage());
            $failed = true;
        }
    };

    $check('app key configured', fn (): bool => filled(config('app.key')));
    $check('database ping', fn (): bool => DB::select('select 1 as health_check') !== []);
    $check('cache write/read', function (): bool {
        $key = 'neogiga:smoke:' . app()->environment();
        Cache::put($key, 'ok', 60);

        return Cache::get($key) === 'ok';
    });
    $check('jobs table visible', fn (): bool => config('queue.default') !== 'database' || Schema::hasTable('jobs'));
    $check('storage framework writable', fn (): bool => is_writable(storage_path('framework')));
    $check('bootstrap cache writable', fn (): bool => is_writable(base_path('bootstrap/cache')));

    return $failed ? self::FAILURE : self::SUCCESS;
})->purpose('Run production-safe NeoGiga smoke checks without migrations or data changes.');

Artisan::command('jlcpcb:repair-skus {--apply : Persist SKU changes. Without this flag the command is a dry-run.} {--limit=0 : Maximum rows to process, 0 means all.}', function () {
    if (! Schema::hasTable('catalog_product_sources') || ! Schema::hasTable('catalog_sources') || ! Schema::hasTable('products')) {
        $this->error('Required catalog source/product tables are missing.');

        return self::FAILURE;
    }

    $limit = max(0, (int) $this->option('limit'));
    $apply = (bool) $this->option('apply');

    $query = DB::table('catalog_product_sources as cps')
        ->join('catalog_sources as cs', 'cs.id', '=', 'cps.source_id')
        ->join('products as p', 'p.id', '=', 'cps.product_id')
        ->where('cs.code', 'jlcpcb_parts_database')
        ->where('p.sku', 'like', 'JLCPCB-%')
        ->select('p.id as product_id', 'p.sku', 'cps.source_part_id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $rows = $query->orderBy('p.id')->get();
    $planned = [];
    $conflicts = [];

    foreach ($rows as $row) {
        $targetSku = 'NG-'.$row->source_part_id;
        $owner = DB::table('products')->where('sku', $targetSku)->where('id', '<>', $row->product_id)->first(['id', 'sku']);
        if ($owner) {
            $conflicts[] = [
                'product_id' => $row->product_id,
                'current_sku' => $row->sku,
                'target_sku' => $targetSku,
                'existing_product_id' => $owner->id,
            ];
            continue;
        }

        $planned[] = [
            'product_id' => (int) $row->product_id,
            'current_sku' => $row->sku,
            'target_sku' => $targetSku,
        ];
    }

    $this->line('Rows scanned: '.$rows->count());
    $this->line('Safe updates: '.count($planned));
    $this->line('Conflicts: '.count($conflicts));

    foreach (array_slice($planned, 0, 10) as $item) {
        $this->line("PLAN product #{$item['product_id']}: {$item['current_sku']} -> {$item['target_sku']}");
    }
    foreach (array_slice($conflicts, 0, 10) as $item) {
        $this->warn("CONFLICT product #{$item['product_id']}: {$item['target_sku']} already belongs to #{$item['existing_product_id']}");
    }

    if ($conflicts !== []) {
        $this->error('Refusing to apply while conflicts exist.');

        return self::FAILURE;
    }

    if (! $apply) {
        $this->comment('Dry-run only. Re-run with --apply to persist safe SKU repairs.');

        return self::SUCCESS;
    }

    DB::transaction(function () use ($planned) {
        foreach ($planned as $item) {
            DB::table('products')->where('id', $item['product_id'])->update([
                'sku' => $item['target_sku'],
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('product_search_documents')) {
                DB::table('product_search_documents')->where('product_id', $item['product_id'])->update([
                    'sku' => $item['target_sku'],
                    'updated_at' => now(),
                ]);
            }
        }
    });

    $this->info('Updated '.count($planned).' JLCPCB-linked product SKU(s) to NG-*.');

    return self::SUCCESS;
})->purpose('Repair JLCPCB-linked product SKUs from JLCPCB-* to NeoGiga NG-* format.');

Artisan::command('products:activate-drafts-and-images {--apply : Persist changes. Without this flag the command is a dry-run.} {--limit=0 : Maximum products to inspect, 0 means all.}', function () {
    if (! Schema::hasTable('products') || ! Schema::hasTable('product_images')) {
        $this->error('Required products/product_images tables are missing.');

        return self::FAILURE;
    }

    $limit = max(0, (int) $this->option('limit'));
    $apply = (bool) $this->option('apply');
    $targetStatus = 'approved';
    $placeholderPath = '/images/products/neogiga-component-placeholder.svg';
    $fileSize = file_exists(public_path(ltrim($placeholderPath, '/')))
        ? filesize(public_path(ltrim($placeholderPath, '/')))
        : 0;

    $draftQuery = DB::table('products')->where('status', 'draft');
    if ($limit > 0) {
        $draftQuery->whereIn('id', DB::table('products')->where('status', 'draft')->orderBy('id')->limit($limit)->pluck('id'));
    }

    $missingImageQuery = DB::table('products as p')
        ->whereNotExists(function ($query) {
            $query->selectRaw('1')
                ->from('product_images as pi')
                ->whereColumn('pi.product_id', 'p.id')
                ->where('pi.is_active', true);
        })
        ->select('p.id', 'p.name', 'p.sku');

    if ($limit > 0) {
        $missingImageQuery->orderBy('p.id')->limit($limit);
    }

    $draftCount = (clone $draftQuery)->count();
    $missingImages = $missingImageQuery->orderBy('p.id')->get();

    $this->line('Draft products to activate: '.$draftCount);
    $this->line('Products missing active image: '.$missingImages->count());
    $this->line('Target products.status: '.$targetStatus.' (database active catalog state)');
    $this->line('Placeholder image: '.$placeholderPath);
    $this->line('Safety: approval_status and visibility_status are preserved.');

    if (! $apply) {
        $this->comment('Dry-run only. Re-run with --apply to persist activation and placeholder images.');

        return self::SUCCESS;
    }

    DB::transaction(function () use ($draftQuery, $missingImages, $placeholderPath, $fileSize, $targetStatus) {
        $draftQuery->update([
            'status' => $targetStatus,
            'updated_at' => now(),
        ]);

        foreach ($missingImages->chunk(500) as $chunk) {
            DB::table('product_images')->insert($chunk->map(function ($product) use ($placeholderPath, $fileSize) {
                $name = trim((string) ($product->name ?: $product->sku ?: 'NeoGiga product'));

                return [
                    'product_id' => $product->id,
                    'file_path' => $placeholderPath,
                    'file_name' => 'neogiga-component-placeholder.svg',
                    'mime_type' => 'image/svg+xml',
                    'file_size' => $fileSize,
                    'sort_order' => 1,
                    'is_primary' => true,
                    'alt_text' => mb_substr($name.' product image', 0, 255),
                    'caption' => 'NeoGiga catalog placeholder image pending product media review.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all());
        }
    });

    $this->info('Activated '.$draftCount.' draft product(s) and inserted '.$missingImages->count().' placeholder image row(s).');

    return self::SUCCESS;
})->purpose('Activate draft products while preserving approval/visibility, and add placeholder images for products missing images.');

Artisan::command('product-images:audit {--sample=10 : Placeholder sample size.}', function () {
    if (! Schema::hasTable('products') || ! Schema::hasTable('product_images')) {
        $this->error('Required products/product_images tables are missing.');

        return self::FAILURE;
    }

    $placeholderPath = '/images/products/neogiga-component-placeholder.svg';
    $sampleSize = max(0, (int) $this->option('sample'));
    $sourceLicenseColumn = Schema::hasColumn('product_images', 'source_license');
    $checksumColumn = Schema::hasColumn('product_images', 'checksum');

    $totalProducts = DB::table('products')->count();
    $activeRows = DB::table('product_images')->where('is_active', true)->count();
    $productsWithActiveImage = DB::table('products as p')
        ->whereExists(function ($query) {
            $query->selectRaw('1')
                ->from('product_images as pi')
                ->whereColumn('pi.product_id', 'p.id')
                ->where('pi.is_active', true);
        })
        ->count();
    $missingActiveImage = max(0, $totalProducts - $productsWithActiveImage);
    $placeholderRows = DB::table('product_images')->where('file_path', $placeholderPath)->where('is_active', true)->count();
    $licensedRows = $sourceLicenseColumn
        ? DB::table('product_images')->whereNotNull('source_license')->where('is_active', true)->count()
        : 0;
    $checksummedRows = $checksumColumn
        ? DB::table('product_images')->whereNotNull('checksum')->where('is_active', true)->count()
        : 0;

    $this->line('Products: '.$totalProducts);
    $this->line('Active image rows: '.$activeRows);
    $this->line('Products with active image: '.$productsWithActiveImage);
    $this->line('Products missing active image: '.$missingActiveImage);
    $this->line('Active placeholder image rows: '.$placeholderRows);
    $this->line('Active licensed/source-attributed image rows: '.$licensedRows);
    $this->line('Active checksum-attributed image rows: '.$checksummedRows);
    $this->line('Safety: external images are not downloaded by this audit.');

    if ($sampleSize > 0) {
        $samples = DB::table('product_images as pi')
            ->join('products as p', 'p.id', '=', 'pi.product_id')
            ->where('pi.file_path', $placeholderPath)
            ->where('pi.is_active', true)
            ->orderBy('p.id')
            ->limit($sampleSize)
            ->get(['p.id', 'p.sku', 'p.name']);

        if ($samples->isNotEmpty()) {
            $this->table(['Product ID', 'SKU', 'Name'], $samples->map(fn ($row) => [
                $row->id,
                $row->sku,
                Str::limit((string) $row->name, 80),
            ])->all());
        }
    }

    return self::SUCCESS;
})->purpose('Audit product image coverage and placeholder/licensed attribution status.');

Artisan::command('product-images:import-licensed-manifest
    {manifest : CSV file with product_id or manufacturer+mpn plus local_path/file_path and license fields.}
    {--apply : Persist changes. Without this flag the command is a dry-run.}
    {--limit=0 : Maximum manifest rows to inspect, 0 means all.}
    {--media-root= : Base directory used to resolve relative local_path values.}
    {--replace-placeholder : Deactivate the NeoGiga placeholder image when a licensed image is inserted.}', function () {
    if (! Schema::hasTable('products') || ! Schema::hasTable('product_images')) {
        $this->error('Required products/product_images tables are missing.');

        return self::FAILURE;
    }

    foreach (['source_name', 'source_license', 'source_url', 'original_url', 'checksum', 'width', 'height', 'copyright', 'downloaded_at', 'metadata'] as $column) {
        if (! Schema::hasColumn('product_images', $column)) {
            $this->error("product_images.{$column} is missing. Run migrations before importing licensed images.");

            return self::FAILURE;
        }
    }

    $manifest = (string) $this->argument('manifest');
    if (! is_file($manifest) || ! is_readable($manifest)) {
        $this->error('Manifest file is not readable: '.$manifest);

        return self::FAILURE;
    }

    $apply = (bool) $this->option('apply');
    $replacePlaceholder = (bool) $this->option('replace-placeholder');
    $limit = max(0, (int) $this->option('limit'));
    $mediaRoot = (string) ($this->option('media-root') ?: dirname($manifest));
    $placeholderPath = '/images/products/neogiga-component-placeholder.svg';
    $targetRoot = public_path('storage/catalog/product-images');

    $normalizeMpn = fn (?string $value): string => strtoupper(preg_replace('/[^A-Z0-9]+/i', '', (string) $value) ?? '');
    $allowed = fn ($value): bool => in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'allowed', 'permitted'], true);

    $handle = fopen($manifest, 'r');
    if ($handle === false) {
        $this->error('Could not open manifest file: '.$manifest);

        return self::FAILURE;
    }

    $headers = fgetcsv($handle);
    if (! is_array($headers)) {
        fclose($handle);
        $this->error('Manifest is empty or not valid CSV.');

        return self::FAILURE;
    }
    $headers = array_map(fn ($header) => Str::snake(trim((string) $header)), $headers);

    $planned = [];
    $skipped = [];
    $rowNumber = 1;
    while (($row = fgetcsv($handle)) !== false) {
        $rowNumber++;
        if ($limit > 0 && count($planned) + count($skipped) >= $limit) {
            break;
        }

        $data = array_combine($headers, array_pad($row, count($headers), null));
        if (! is_array($data)) {
            $skipped[] = ['row' => $rowNumber, 'reason' => 'invalid CSV row'];
            continue;
        }

        $license = trim((string) ($data['source_license'] ?? $data['license'] ?? $data['license_note'] ?? ''));
        $sourceName = trim((string) ($data['source_name'] ?? $data['source'] ?? ''));
        $sourceUrl = trim((string) ($data['source_url'] ?? ''));
        $redistribution = $allowed($data['redistribution_allowed'] ?? $data['image_redistribution_allowed'] ?? false);
        $localPath = trim((string) ($data['local_path'] ?? $data['image_local_path'] ?? $data['file_path'] ?? ''));

        if ($license === '' || in_array(strtolower($license), ['unknown', 'n/a', 'none'], true)) {
            $skipped[] = ['row' => $rowNumber, 'reason' => 'missing/unknown source license'];
            continue;
        }
        if ($sourceName === '' || $sourceUrl === '') {
            $skipped[] = ['row' => $rowNumber, 'reason' => 'source_name/source_url required'];
            continue;
        }
        if (! $redistribution) {
            $skipped[] = ['row' => $rowNumber, 'reason' => 'redistribution not explicitly allowed'];
            continue;
        }
        if ($localPath === '') {
            $skipped[] = ['row' => $rowNumber, 'reason' => 'local_path/file_path required; external hotlink/download skipped'];
            continue;
        }

        $sourceFile = str_starts_with($localPath, '/') ? $localPath : rtrim($mediaRoot, '/').'/'.$localPath;
        if (! is_file($sourceFile) || ! is_readable($sourceFile)) {
            $skipped[] = ['row' => $rowNumber, 'reason' => 'local image file not readable'];
            continue;
        }

        $productQuery = DB::table('products');
        if (! empty($data['product_id'])) {
            $productQuery->where('id', (int) $data['product_id']);
        } else {
            $normalized = $normalizeMpn((string) ($data['mpn'] ?? $data['manufacturer_part_number'] ?? ''));
            if ($normalized === '') {
                $skipped[] = ['row' => $rowNumber, 'reason' => 'product_id or MPN required'];
                continue;
            }

            $productQuery->where('normalized_mpn', $normalized);
            $manufacturer = trim((string) ($data['manufacturer'] ?? ''));
            if ($manufacturer !== '' && Schema::hasColumn('products', 'manufacturer_name')) {
                $productQuery->whereRaw('lower(manufacturer_name) = ?', [strtolower($manufacturer)]);
            }
        }

        $product = $productQuery->orderBy('id')->first(['id', 'name', 'sku']);
        if (! $product) {
            $skipped[] = ['row' => $rowNumber, 'reason' => 'matching product not found'];
            continue;
        }

        $checksum = hash_file('sha256', $sourceFile);
        $duplicate = DB::table('product_images')->where('product_id', $product->id)->where('checksum', $checksum)->exists();
        if ($duplicate) {
            $skipped[] = ['row' => $rowNumber, 'reason' => 'duplicate checksum for product'];
            continue;
        }

        $extension = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION) ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'], true)) {
            $skipped[] = ['row' => $rowNumber, 'reason' => 'unsupported image extension'];
            continue;
        }

        $planned[] = [
            'row' => $rowNumber,
            'product' => $product,
            'source_file' => $sourceFile,
            'checksum' => $checksum,
            'extension' => $extension,
            'source_name' => $sourceName,
            'source_url' => $sourceUrl,
            'source_license' => $license,
            'original_url' => trim((string) ($data['original_url'] ?? $data['image_url'] ?? '')) ?: null,
            'copyright' => trim((string) ($data['copyright'] ?? $data['image_copyright'] ?? '')) ?: null,
            'alt_text' => trim((string) ($data['alt_text'] ?? $data['image_alt_text'] ?? $product->name.' product image')),
            'caption' => trim((string) ($data['caption'] ?? $data['image_caption'] ?? 'Licensed product image from '.$sourceName)),
        ];
    }
    fclose($handle);

    $this->line('Manifest rows planned for import: '.count($planned));
    $this->line('Manifest rows skipped: '.count($skipped));
    $this->line('Safety: only local files with explicit redistribution permission are imported.');
    if ($skipped) {
        $this->table(['Row', 'Reason'], array_slice(array_map(fn ($row) => [$row['row'], $row['reason']], $skipped), 0, 20));
    }

    if (! $apply) {
        $this->comment('Dry-run only. Re-run with --apply to copy files and insert image rows.');

        return self::SUCCESS;
    }

    if (! is_dir($targetRoot) && ! mkdir($targetRoot, 0775, true) && ! is_dir($targetRoot)) {
        $this->error('Could not create target image directory: '.$targetRoot);

        return self::FAILURE;
    }

    DB::transaction(function () use ($planned, $targetRoot, $placeholderPath, $replacePlaceholder) {
        foreach ($planned as $plan) {
            $product = $plan['product'];
            $productDir = $targetRoot.'/'.$product->id;
            if (! is_dir($productDir) && ! mkdir($productDir, 0775, true) && ! is_dir($productDir)) {
                throw new RuntimeException('Could not create product image directory: '.$productDir);
            }

            $fileName = $plan['checksum'].'.'.$plan['extension'];
            $destination = $productDir.'/'.$fileName;
            if (! is_file($destination) && ! copy($plan['source_file'], $destination)) {
                throw new RuntimeException('Could not copy image file to '.$destination);
            }

            $size = @getimagesize($destination) ?: [null, null];
            $mime = mime_content_type($destination) ?: match ($plan['extension']) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                'avif' => 'image/avif',
                'gif' => 'image/gif',
                default => 'image/jpeg',
            };

            if ($replacePlaceholder) {
                DB::table('product_images')
                    ->where('product_id', $product->id)
                    ->where('file_path', $placeholderPath)
                    ->update(['is_active' => false, 'updated_at' => now()]);
            }

            DB::table('product_images')->insert([
                'product_id' => $product->id,
                'file_path' => '/storage/catalog/product-images/'.$product->id.'/'.$fileName,
                'original_url' => $plan['original_url'],
                'source_url' => $plan['source_url'],
                'source_name' => $plan['source_name'],
                'source_license' => $plan['source_license'],
                'copyright' => $plan['copyright'],
                'checksum' => $plan['checksum'],
                'width' => $size[0],
                'height' => $size[1],
                'downloaded_at' => now(),
                'metadata' => json_encode(['imported_by' => 'product-images:import-licensed-manifest', 'advisory' => 'Image rights supplied by licensed manifest.']),
                'file_name' => $fileName,
                'mime_type' => $mime,
                'file_size' => filesize($destination) ?: null,
                'sort_order' => 0,
                'is_primary' => true,
                'alt_text' => mb_substr($plan['alt_text'], 0, 255),
                'caption' => mb_substr($plan['caption'], 0, 255),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    });

    $this->info('Imported '.count($planned).' licensed product image(s).');

    return self::SUCCESS;
})->purpose('Import licensed local product images from a manifest, with dry-run and rights gates.');

Artisan::command('product-images:discover-candidates
    {--apply : Persist candidates. Without this flag the command is a dry-run.}
    {--limit=100 : Maximum products to inspect.}
    {--min-confidence=0.70 : Minimum candidate confidence to store.}
    {--timeout=8 : HTTP timeout seconds per source page.}', function () {
    if (! Schema::hasTable('products') || ! Schema::hasTable('catalog_product_sources') || ! Schema::hasTable('catalog_sources')) {
        $this->error('Required products/catalog source tables are missing.');

        return self::FAILURE;
    }
    if (! Schema::hasTable('product_image_candidates')) {
        $this->error('product_image_candidates table is missing. Run migrations before candidate discovery.');

        return self::FAILURE;
    }

    $apply = (bool) $this->option('apply');
    $limit = max(1, (int) $this->option('limit'));
    $minConfidence = max(0.0, min(1.0, (float) $this->option('min-confidence')));
    $timeout = max(2, min(30, (int) $this->option('timeout')));
    $placeholderPath = '/images/products/neogiga-component-placeholder.svg';
    $extractCandidates = function (string $html, string $baseUrl, string $mpn): array {
        $urls = [];
        $mpnNeedle = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $mpn) ?? '');
        $add = function (?string $url, string $selector, string $context = '') use (&$urls, $baseUrl, $mpnNeedle): void {
            $url = trim((string) $url);
            if ($url === '' || str_starts_with($url, 'data:')) {
                return;
            }
            if (str_starts_with($url, '//')) {
                $url = 'https:'.$url;
            } elseif (str_starts_with($url, '/')) {
                $parts = parse_url($baseUrl);
                $url = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').$url;
            } elseif (! preg_match('#^https?://#i', $url)) {
                $url = rtrim(dirname($baseUrl), '/').'/'.$url;
            }

            if (! preg_match('/\.(jpe?g|png|webp|avif|gif)(\?|$)/i', $url)) {
                return;
            }

            $contextNormalized = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $context) ?? '');
            $matchedMpn = $mpnNeedle !== '' && str_contains($contextNormalized.$url, $mpnNeedle);
            $confidence = $selector === 'meta' ? 0.75 : 0.55;
            if ($matchedMpn) {
                $confidence += 0.20;
            }

            $urls[$url] = [
                'url' => $url,
                'selector' => $selector,
                'matched_mpn' => $matchedMpn,
                'confidence' => min(0.95, $confidence),
            ];
        };

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//meta[@property="og:image" or @property="og:image:secure_url" or @name="twitter:image"]') ?: [] as $node) {
            $add($node->attributes?->getNamedItem('content')?->nodeValue, 'meta');
        }

        foreach ($xpath->query('//img[@src]') ?: [] as $node) {
            $src = $node->attributes?->getNamedItem('src')?->nodeValue;
            $alt = $node->attributes?->getNamedItem('alt')?->nodeValue ?? '';
            $class = $node->attributes?->getNamedItem('class')?->nodeValue ?? '';
            $add($src, 'img', $alt.' '.$class);
        }

        return array_values($urls);
    };

    $rows = DB::table('products as p')
        ->join('catalog_product_sources as cps', 'cps.product_id', '=', 'p.id')
        ->join('catalog_sources as cs', 'cs.id', '=', 'cps.source_id')
        ->whereNotNull('cps.source_url')
        ->whereExists(function ($query) use ($placeholderPath) {
            $query->selectRaw('1')
                ->from('product_images as pi')
                ->whereColumn('pi.product_id', 'p.id')
                ->where('pi.file_path', $placeholderPath)
                ->where('pi.is_active', true);
        })
        ->whereNotExists(function ($query) {
            $query->selectRaw('1')
                ->from('product_image_candidates as pic')
                ->whereColumn('pic.product_id', 'p.id');
        })
        ->orderBy('p.id')
        ->limit($limit)
        ->get([
            'p.id as product_id',
            'p.name',
            'p.mpn',
            'p.manufacturer_name',
            'cps.source_url',
            'cs.name as source_name',
        ]);

    $this->line('Products inspected: '.$rows->count());
    $this->line('Safety: candidates remain hidden with rights_status=pending_review; no image is downloaded or published.');

    $candidates = [];
    $skipped = [];
    foreach ($rows as $row) {
        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['User-Agent' => 'NeoGigaImageCandidateBot/1.0 (+https://neogiga.com)'])
                ->get((string) $row->source_url);
        } catch (Throwable $exception) {
            $skipped[] = [$row->product_id, 'fetch failed'];
            continue;
        }

        if (! $response->ok() || ! str_contains(strtolower((string) $response->header('content-type')), 'text/html')) {
            $skipped[] = [$row->product_id, 'non-html or bad response'];
            continue;
        }

        $found = $extractCandidates((string) $response->body(), (string) $row->source_url, (string) $row->mpn);
        if (! $found) {
            $skipped[] = [$row->product_id, 'no candidate image'];
            continue;
        }

        $found = array_values(array_filter($found, fn ($candidate) => (float) $candidate['confidence'] >= $minConfidence));
        if (! $found) {
            $skipped[] = [$row->product_id, 'only low-confidence/generic image candidates'];
            continue;
        }

        foreach ($found as $candidate) {
            $candidates[] = [
                'product_id' => $row->product_id,
                'candidate_url' => $candidate['url'],
                'source_page_url' => $row->source_url,
                'source_name' => $row->source_name,
                'manufacturer' => $row->manufacturer_name,
                'mpn' => $row->mpn,
                'discovered_by' => 'product-images:discover-candidates',
                'rights_status' => 'pending_review',
                'confidence_score' => $candidate['confidence'],
                'evidence' => json_encode([
                    'selector' => $candidate['selector'],
                    'matched_mpn' => $candidate['matched_mpn'],
                    'advisory' => 'Candidate URL only. Do not download/publish until rights are approved.',
                ]),
                'discovered_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    }

    $this->line('Candidate image URL(s) found: '.count($candidates));
    $this->line('Skipped product(s): '.count($skipped));
    $this->line('Minimum confidence: '.$minConfidence);
    if ($skipped) {
        $this->table(['Product ID', 'Reason'], array_slice($skipped, 0, 20));
    }

    if (! $apply) {
        $this->comment('Dry-run only. Re-run with --apply to store hidden candidate URLs for review.');

        return self::SUCCESS;
    }

    foreach (collect($candidates)->chunk(250) as $chunk) {
        DB::table('product_image_candidates')->upsert(
            $chunk->all(),
            ['product_id', 'candidate_url'],
            ['source_page_url', 'source_name', 'manufacturer', 'mpn', 'confidence_score', 'evidence', 'discovered_at', 'updated_at']
        );
    }

    $this->info('Stored '.count($candidates).' hidden image candidate URL(s) for rights review.');

    return self::SUCCESS;
})->purpose('Discover hidden product image candidates from existing public source pages without downloading or publishing images.');

Schedule::job(new DetectAbandonedCartsJob)->everyFifteenMinutes();
Schedule::job(new CalculateTrendingProductsJob)->hourly();
Schedule::job(new CalculateTrendingCategoriesJob)->hourly();
Schedule::job(new CalculateTopSearchTermsJob)->hourly();
Schedule::job(new RefreshCustomerSegmentJob)->daily();
Schedule::job(new GenerateRegionalSalesReportJob)->daily();
