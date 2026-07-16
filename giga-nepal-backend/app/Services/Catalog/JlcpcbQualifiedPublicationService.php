<?php

namespace App\Services\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class JlcpcbQualifiedPublicationService
{
    private const PLAN_VERSION = 'jlcpcb-qualified-publication-v3';

    private const PLAN_CHUNK_SIZE = 1_000;

    private const AUDIT_KEY = 'jlcpcb_qualified_publication_v1';

    /**
     * Reuse the qualified-publication backup contract for other governed JLC
     * catalog upgrades without weakening or duplicating its checksum checks.
     *
     * @return array{directory:string,manifest_sha256:string,restore_verification_sha256:string,verified_files:int,status:string}
     */
    public function verifyBackup(string $directory): array
    {
        return $this->verifyBackupDirectory($directory);
    }

    /** @var list<string> */
    private const BLOCKERS = [
        'rejected_decision',
        'incomplete_identity',
        'missing_raw_snapshot',
        'missing_distributor_offer',
        'missing_global_usd_price',
        'missing_active_local_image',
        'below_minimum_data_quality',
        'other_unapproved_source',
        'unsupported_visibility',
    ];

    /** @return array<string, mixed> */
    public function plan(?float $minimumQuality = null): array
    {
        $this->assertSchema();

        return $this->buildPlan($this->minimumQuality($minimumQuality), null, true);
    }

    /**
     * Read-only readiness for targeted admin review. This deliberately shares
     * the exact qualification blockers used by the governed CLI workflow.
     *
     * @param  list<int>  $sourceLinkIds
     * @return array<int, array{ready:bool,blockers:list<string>,minimum_data_quality_score:float}>
     */
    public function readinessForSourceLinks(array $sourceLinkIds, ?float $minimumQuality = null): array
    {
        $this->assertSchema();
        $sourceLinkIds = array_values(array_unique(array_filter(
            array_map('intval', $sourceLinkIds),
            static fn (int $id): bool => $id > 0,
        )));
        if ($sourceLinkIds === []) {
            return [];
        }

        $quality = $this->minimumQuality($minimumQuality);

        return $this->candidateQuery($sourceLinkIds)
            ->get()
            ->mapWithKeys(function (object $row) use ($quality): array {
                $blockers = $this->blockersFor($row, $quality);

                return [(int) $row->source_link_id => [
                    'ready' => $blockers === [],
                    'blockers' => $blockers,
                    'minimum_data_quality_score' => $quality,
                ]];
            })
            ->all();
    }

    /** @param array<string, mixed> $plan @return array<string, mixed> */
    public function forOutput(array $plan): array
    {
        unset($plan['eligible_rows']);

        return $plan;
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(
        string $expectedPlanHash,
        string $backupDirectory,
        ?float $minimumQuality = null,
        ?int $batchSize = null,
    ): array {
        $this->assertSchema();
        $quality = $this->minimumQuality($minimumQuality);
        $chunkSize = $this->batchSize($batchSize);
        $expectedPlanHash = strtolower(trim($expectedPlanHash));

        if (preg_match('/^[a-f0-9]{64}$/', $expectedPlanHash) !== 1) {
            throw new RuntimeException('The exact 64-character SHA-256 --plan-hash from the current dry run is required.');
        }

        $backup = $this->verifyBackupDirectory($backupDirectory);
        // Compare the complete live universe before looking up the artifact so
        // stale or invented hashes retain the original fail-closed contract.
        $currentPlan = $this->buildPlan($quality);
        if (! hash_equals((string) $currentPlan['plan_hash'], $expectedPlanHash)) {
            throw new RuntimeException('The supplied plan hash does not match the current qualified-publication plan; run a new dry run.');
        }
        $artifact = $this->openVerifiedPlanArtifact($expectedPlanHash, $quality);

        try {
            // Complete the expensive, read-only universe comparison before the
            // first bounded write. This catches additions, removals, swaps, and
            // fingerprint mutations without holding 500k plan rows in memory.
            if ((int) $currentPlan['eligible_products'] !== (int) $artifact['eligible_products']
                || ! hash_equals((string) $currentPlan['eligible_digest'], (string) $artifact['eligible_digest'])) {
                throw new RuntimeException('The immutable dry-run artifact does not match the current qualified-publication set.');
            }
            $this->assertPlanArtifactUnchanged($artifact['handle'], $artifact['stat']);

            $totals = [
                'changed_products' => 0,
                'unchanged_products' => 0,
                'source_reviews_approved' => 0,
                'offer_reviews_approved' => 0,
                'product_rows_updated' => 0,
                'batches_committed' => 0,
            ];

            $appliedEligible = 0;
            $this->eachPlanArtifactChunk($artifact['handle'], $chunkSize, function (array $chunk) use (
                $quality,
                $expectedPlanHash,
                $backup,
                &$totals,
                &$appliedEligible,
            ): void {
                $result = DB::transaction(function () use ($chunk, $quality, $expectedPlanHash, $backup): array {
                    $sourceLinkIds = array_map(static fn (array $row): int => (int) $row['source_link_id'], $chunk);
                    $productIds = array_values(array_unique(array_map(
                        static fn (array $row): int => (int) $row['product_id'],
                        $chunk,
                    )));
                    sort($sourceLinkIds, SORT_NUMERIC);
                    sort($productIds, SORT_NUMERIC);

                    $this->lockChunkDependencies($productIds);

                    $current = $this->buildPlan($quality, $sourceLinkIds);
                    if ($chunk !== $current['eligible_rows']) {
                        throw new RuntimeException('A product or source link from the immutable dry-run plan changed during apply; the current bounded transaction was rolled back.');
                    }

                    return $this->applyChunk($chunk, $expectedPlanHash, $backup);
                }, 3);

                foreach ($totals as $key => $value) {
                    $totals[$key] = $value + (int) $result[$key];
                }
                $appliedEligible += count($chunk);
            });
            $this->assertPlanArtifactUnchanged($artifact['handle'], $artifact['stat']);

            if ($appliedEligible !== (int) $artifact['eligible_products']) {
                throw new RuntimeException('The immutable dry-run artifact ended unexpectedly during bounded apply; committed batches remain auditable and a new dry run is required.');
            }

            if ($totals['changed_products'] > 0) {
                Cache::forget('seo:sitemap');
            }

            return [
                'status' => 'completed',
                'source_code' => (string) config('jlcpcb_qualified_publication.source_code'),
                'minimum_data_quality_score' => $quality,
                'eligible_products' => (int) $artifact['eligible_products'],
                'plan_hash' => $expectedPlanHash,
                'eligible_digest' => (string) $artifact['eligible_digest'],
                'backup' => $backup,
            ] + $totals;
        } finally {
            $this->closePlanArtifact($artifact['handle']);
        }
    }

    /**
     * @param  list<int>|null  $sourceLinkIds
     * @return array<string, mixed>
     */
    private function buildPlan(float $minimumQuality, ?array $sourceLinkIds = null, bool $persistArtifact = false): array
    {
        if ($persistArtifact && $sourceLinkIds !== null) {
            throw new RuntimeException('Only a complete qualified-publication plan can be persisted.');
        }

        $blockers = array_fill_keys(self::BLOCKERS, 0);
        $eligible = [];
        $eligibleCount = 0;
        $eligibleDigest = hash_init('sha256');
        $blockedProducts = 0;
        $sourceProducts = 0;
        $artifactWriter = $persistArtifact ? $this->openPlanArtifactWriter() : null;

        try {
            $this->eachCandidateChunk($sourceLinkIds, self::PLAN_CHUNK_SIZE, function ($rows) use (
                $minimumQuality,
                $sourceLinkIds,
                $artifactWriter,
                &$blockers,
                &$eligible,
                &$eligibleCount,
                $eligibleDigest,
                &$blockedProducts,
                &$sourceProducts,
            ): void {
                foreach ($rows as $row) {
                    $sourceProducts++;
                    $reasons = $this->blockersFor($row, $minimumQuality);
                    if ($reasons !== []) {
                        $blockedProducts++;
                        foreach ($reasons as $reason) {
                            $blockers[$reason]++;
                        }

                        continue;
                    }

                    $entry = $this->eligibleEntry($row, $minimumQuality);
                    if ($entry === null) {
                        throw new RuntimeException('Eligible publication row classification changed during the same bounded plan scan.');
                    }
                    $line = $this->json($entry)."\n";
                    $eligibleCount++;
                    hash_update($eligibleDigest, $line);
                    if ($artifactWriter !== null && fwrite($artifactWriter['handle'], $line) !== strlen($line)) {
                        throw new RuntimeException('The immutable qualified-publication plan artifact could not be written completely.');
                    }
                    if ($sourceLinkIds !== null) {
                        $eligible[] = $entry;
                    }
                }
            });
        } catch (\Throwable $exception) {
            if ($artifactWriter !== null) {
                $this->discardPlanArtifactWriter($artifactWriter);
            }

            throw $exception;
        }

        $digest = hash_final($eligibleDigest);
        $planHash = $this->planHash($minimumQuality, $eligibleCount, $digest);
        if ($artifactWriter !== null) {
            $this->finalizePlanArtifactWriter($artifactWriter, $planHash, $digest);
        }

        return [
            'mode' => 'dry_run',
            'plan_version' => self::PLAN_VERSION,
            'source_code' => (string) config('jlcpcb_qualified_publication.source_code'),
            'minimum_data_quality_score' => $minimumQuality,
            'source_products' => $sourceProducts,
            'eligible_products' => $eligibleCount,
            'blocked_products' => $blockedProducts,
            'blocker_counts' => $blockers,
            'plan_hash' => $planHash,
            'eligible_digest' => $digest,
            'plan_artifact_status' => $persistArtifact ? 'immutable' : 'not_persisted',
            'eligible_rows' => $eligible,
        ];
    }

    private function planHash(float $minimumQuality, int $eligibleCount, string $eligibleDigest): string
    {
        return hash('sha256', $this->json([
            'version' => self::PLAN_VERSION,
            'source_code' => (string) config('jlcpcb_qualified_publication.source_code'),
            'marketplace_code' => strtoupper((string) config('jlcpcb_qualified_publication.marketplace.code')),
            'currency' => strtoupper((string) config('jlcpcb_qualified_publication.marketplace.currency')),
            'minimum_data_quality_score' => number_format($minimumQuality, 4, '.', ''),
            'eligible_count' => $eligibleCount,
            'eligible_digest' => $eligibleDigest,
        ]));
    }

    /** @return array{handle:resource,path:string} */
    private function openPlanArtifactWriter(): array
    {
        $root = $this->planArtifactRoot();
        $path = tempnam($root, '.pending-publication-plan-');
        if ($path === false) {
            throw new RuntimeException('A temporary qualified-publication plan artifact could not be created.');
        }

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            @unlink($path);
            throw new RuntimeException('The temporary qualified-publication plan artifact could not be opened.');
        }

        return ['handle' => $handle, 'path' => $path];
    }

    /** @param array{handle:resource,path:string} $writer */
    private function discardPlanArtifactWriter(array $writer): void
    {
        if (is_resource($writer['handle'])) {
            fclose($writer['handle']);
        }
        if (is_file($writer['path'])) {
            @unlink($writer['path']);
        }
    }

    /** @param array{handle:resource,path:string} $writer */
    private function finalizePlanArtifactWriter(array $writer, string $planHash, string $eligibleDigest): void
    {
        $handle = $writer['handle'];
        $path = $writer['path'];
        $lockHandle = null;

        try {
            if (! fflush($handle)) {
                throw new RuntimeException('The immutable qualified-publication plan artifact could not be flushed.');
            }
            if (function_exists('fsync') && ! fsync($handle)) {
                throw new RuntimeException('The immutable qualified-publication plan artifact could not be synchronized.');
            }
            fclose($handle);

            $actualDigest = hash_file('sha256', $path);
            if (! is_string($actualDigest) || ! hash_equals($eligibleDigest, strtolower($actualDigest))) {
                throw new RuntimeException('The qualified-publication plan artifact failed its write checksum.');
            }

            $root = $this->planArtifactRoot();
            $lockHandle = fopen($root.DIRECTORY_SEPARATOR.'.artifact.lock', 'c+b');
            if ($lockHandle === false || ! flock($lockHandle, LOCK_EX)) {
                throw new RuntimeException('The qualified-publication plan artifact lock could not be acquired.');
            }

            $finalPath = $this->planArtifactPath($planHash);
            if (is_file($finalPath)) {
                if (is_link($finalPath)) {
                    throw new RuntimeException('An immutable qualified-publication plan artifact cannot be a symbolic link.');
                }
                $existingDigest = hash_file('sha256', $finalPath);
                if (! is_string($existingDigest) || ! hash_equals($eligibleDigest, strtolower($existingDigest))) {
                    throw new RuntimeException('An immutable qualified-publication plan artifact already exists with unexpected contents.');
                }
                @unlink($path);
            } elseif (! rename($path, $finalPath)) {
                throw new RuntimeException('The immutable qualified-publication plan artifact could not be finalized atomically.');
            }

            if (! chmod($finalPath, 0440)) {
                throw new RuntimeException('The qualified-publication plan artifact could not be made read-only.');
            }
        } catch (\Throwable $exception) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (is_file($path)) {
                @unlink($path);
            }

            throw $exception;
        } finally {
            if (is_resource($lockHandle)) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        }
    }

    /** @return array{handle:resource,eligible_products:int,eligible_digest:string,stat:array<string,int>} */
    private function openVerifiedPlanArtifact(string $planHash, float $minimumQuality): array
    {
        $path = $this->planArtifactPath($planHash);
        if (! is_file($path) || is_link($path)) {
            throw new RuntimeException('The immutable dry-run plan artifact is missing; run a new dry run before apply.');
        }
        $permissions = fileperms($path);
        if (is_int($permissions) && ($permissions & 0222) !== 0) {
            throw new RuntimeException('The immutable dry-run plan artifact must remain read-only.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false || ! flock($handle, LOCK_SH)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new RuntimeException('The immutable dry-run plan artifact could not be opened and locked.');
        }

        try {
            $verification = $this->scanPlanArtifact($handle);
            $computedPlanHash = $this->planHash(
                $minimumQuality,
                $verification['eligible_products'],
                $verification['eligible_digest'],
            );
            if (! hash_equals($planHash, $computedPlanHash)) {
                throw new RuntimeException('The immutable dry-run plan artifact does not match the supplied plan hash.');
            }
            $stat = fstat($handle);
            if (! is_array($stat)) {
                throw new RuntimeException('The immutable dry-run plan artifact metadata could not be read.');
            }
            rewind($handle);

            return [
                'handle' => $handle,
                'eligible_products' => $verification['eligible_products'],
                'eligible_digest' => $verification['eligible_digest'],
                'stat' => [
                    'dev' => (int) $stat['dev'],
                    'ino' => (int) $stat['ino'],
                    'size' => (int) $stat['size'],
                    'mtime' => (int) $stat['mtime'],
                ],
            ];
        } catch (\Throwable $exception) {
            flock($handle, LOCK_UN);
            fclose($handle);

            throw $exception;
        }
    }

    /** @param resource $handle @return array{eligible_products:int,eligible_digest:string} */
    private function scanPlanArtifact($handle): array
    {
        rewind($handle);
        $digest = hash_init('sha256');
        $count = 0;
        $lastSourceLinkId = 0;
        while (($line = fgets($handle)) !== false) {
            $count++;
            $this->parsePlanArtifactEntry($line, $count, $lastSourceLinkId);
            hash_update($digest, $line);
        }
        if (! feof($handle)) {
            throw new RuntimeException('The immutable dry-run plan artifact could not be read completely.');
        }

        return [
            'eligible_products' => $count,
            'eligible_digest' => hash_final($digest),
        ];
    }

    /** @param resource $handle @param callable(list<array{source_link_id:int,product_id:int,fingerprint:string}>):void $callback */
    private function eachPlanArtifactChunk($handle, int $chunkSize, callable $callback): void
    {
        rewind($handle);
        $chunk = [];
        $lineNumber = 0;
        $lastSourceLinkId = 0;
        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $chunk[] = $this->parsePlanArtifactEntry($line, $lineNumber, $lastSourceLinkId);
            if (count($chunk) === $chunkSize) {
                $callback($chunk);
                $chunk = [];
            }
        }
        if (! feof($handle)) {
            throw new RuntimeException('The immutable dry-run plan artifact could not be read completely during apply.');
        }
        if ($chunk !== []) {
            $callback($chunk);
        }
    }

    /** @return array{source_link_id:int,product_id:int,fingerprint:string} */
    private function parsePlanArtifactEntry(string $line, int $lineNumber, int &$lastSourceLinkId): array
    {
        if (! str_ends_with($line, "\n")) {
            throw new RuntimeException("The immutable dry-run plan artifact has an incomplete line at {$lineNumber}.");
        }

        try {
            $entry = json_decode(substr($line, 0, -1), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new RuntimeException("The immutable dry-run plan artifact contains invalid JSON on line {$lineNumber}.");
        }
        if (! is_array($entry)
            || array_keys($entry) !== ['source_link_id', 'product_id', 'fingerprint']
            || ! is_int($entry['source_link_id'])
            || ! is_int($entry['product_id'])
            || $entry['source_link_id'] <= $lastSourceLinkId
            || $entry['product_id'] < 1
            || ! is_string($entry['fingerprint'])
            || preg_match('/^[a-f0-9]{64}$/', $entry['fingerprint']) !== 1
            || substr($line, 0, -1) !== $this->json($entry)) {
            throw new RuntimeException("The immutable dry-run plan artifact has a non-canonical entry on line {$lineNumber}.");
        }

        $lastSourceLinkId = $entry['source_link_id'];

        return $entry;
    }

    /** @param resource $handle */
    private function closePlanArtifact($handle): void
    {
        if (is_resource($handle)) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** @param resource $handle @param array{dev:int,ino:int,size:int,mtime:int} $expected */
    private function assertPlanArtifactUnchanged($handle, array $expected): void
    {
        $current = fstat($handle);
        if (! is_array($current)
            || (int) $current['dev'] !== $expected['dev']
            || (int) $current['ino'] !== $expected['ino']
            || (int) $current['size'] !== $expected['size']
            || (int) $current['mtime'] !== $expected['mtime']) {
            throw new RuntimeException('The immutable dry-run plan artifact changed while apply was in progress.');
        }
    }

    private function planArtifactRoot(): string
    {
        $configured = trim((string) config(
            'jlcpcb_qualified_publication.plan_root',
            storage_path('app/private/jlcpcb-qualified-publication-plans'),
        ));
        if ($configured === '') {
            throw new RuntimeException('The configured qualified-publication plan artifact directory is empty.');
        }
        if (! is_dir($configured) && ! mkdir($configured, 0770, true) && ! is_dir($configured)) {
            throw new RuntimeException('The qualified-publication plan artifact directory could not be created.');
        }
        $root = realpath($configured);
        if ($root === false || ! is_dir($root)) {
            throw new RuntimeException('The qualified-publication plan artifact directory could not be resolved.');
        }

        return rtrim($root, DIRECTORY_SEPARATOR);
    }

    private function planArtifactPath(string $planHash): string
    {
        if (preg_match('/^[a-f0-9]{64}$/', $planHash) !== 1) {
            throw new RuntimeException('A valid qualified-publication plan hash is required for the immutable artifact path.');
        }

        return $this->planArtifactRoot().DIRECTORY_SEPARATOR.$planHash.'.jsonl';
    }

    /** @param list<int> $productIds */
    private function lockChunkDependencies(array $productIds): void
    {
        $lockedProductIds = DB::table('products')
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        if ($lockedProductIds !== $productIds) {
            throw new RuntimeException('A product from the immutable dry-run plan disappeared before its bounded apply.');
        }

        // Lock every source link, not merely the selected JLCPCB link: the
        // eligibility fingerprint depends on other-source review state.
        DB::table('catalog_product_sources')
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
        DB::table('catalog_distributor_offers')
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
        DB::table('marketplace_product_prices')
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
        DB::table('product_images')
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    /** @return array{source_link_id:int,product_id:int,fingerprint:string}|null */
    private function eligibleEntry(object $row, float $minimumQuality): ?array
    {
        if ($this->blockersFor($row, $minimumQuality) !== []) {
            return null;
        }

        $fingerprintMaterial = [
            'source_link_id' => (int) $row->source_link_id,
            'product_id' => (int) $row->product_id,
            'source_part_id' => (string) $row->source_part_id,
            'source_payload_hash' => (string) $row->source_payload_hash,
            'has_raw_snapshot' => (int) $row->has_raw_snapshot,
            'identity_hash' => hash('sha256', $this->json([
                (string) $row->name,
                (string) $row->slug,
                (string) $row->sku,
                (string) $row->mpn,
                $row->category_id === null ? null : (int) $row->category_id,
                $row->brand_id === null ? null : (int) $row->brand_id,
                $row->manufacturer_id === null ? null : (int) $row->manufacturer_id,
                (string) $row->manufacturer_name,
            ])),
            'data_quality_score' => number_format((float) $row->data_quality_score, 2, '.', ''),
            'offer_count' => (int) $row->offer_count,
            'price_count' => (int) $row->price_count,
            'safe_image_count' => (int) $row->safe_image_count,
            'other_unapproved_source_count' => (int) $row->other_unapproved_source_count,
        ];

        return [
            'source_link_id' => (int) $row->source_link_id,
            'product_id' => (int) $row->product_id,
            'fingerprint' => hash('sha256', $this->json($fingerprintMaterial)),
        ];
    }

    /** @param list<int>|null $sourceLinkIds @param callable(\Illuminate\Support\Collection<int, object>):void $callback */
    private function eachCandidateChunk(?array $sourceLinkIds, int $chunkSize, callable $callback): void
    {
        $lastSourceLinkId = 0;
        do {
            $rows = (clone $this->candidateQuery($sourceLinkIds))
                ->where('cps.id', '>', $lastSourceLinkId)
                ->orderBy('cps.id')
                ->limit($chunkSize)
                ->get();
            if ($rows->isEmpty()) {
                break;
            }

            $callback($rows);
            $lastSourceLinkId = (int) $rows->last()->source_link_id;
        } while ($rows->count() === $chunkSize);
    }

    /** @param list<int>|null $sourceLinkIds */
    private function candidateQuery(?array $sourceLinkIds = null): Builder
    {
        $sourceCode = (string) config('jlcpcb_qualified_publication.source_code');
        $marketplaceCode = strtoupper((string) config('jlcpcb_qualified_publication.marketplace.code'));
        $currency = strtoupper((string) config('jlcpcb_qualified_publication.marketplace.currency'));

        $query = DB::table('catalog_product_sources as cps')
            ->join('catalog_sources as cs', 'cs.id', '=', 'cps.source_id')
            ->join('products as p', 'p.id', '=', 'cps.product_id')
            ->where('cs.code', $sourceCode)
            ->select([
                'cps.id as source_link_id',
                'cps.product_id',
                'cps.source_part_id',
                'cps.source_payload_hash',
                'cps.data_quality_score',
                'cps.review_status as source_review_status',
                'p.name',
                'p.slug',
                'p.sku',
                'p.mpn',
                'p.category_id',
                'p.brand_id',
                'p.manufacturer_id',
                'p.manufacturer_name',
                'p.status as product_status',
                'p.approval_status',
                'p.visibility_status',
            ])
            ->selectRaw('CASE WHEN cps.raw_snapshot IS NULL THEN 0 ELSE 1 END AS has_raw_snapshot')
            ->selectSub(function (Builder $offers): void {
                $offers->from('catalog_distributor_offers as offer')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('offer.product_id', 'cps.product_id')
                    ->where('offer.distributor', 'LCSC/JLCPCB')
                    ->whereColumn('offer.sku', 'cps.source_part_id')
                    ->where(function (Builder $review): void {
                        $review->whereNull('offer.review_status')
                            ->orWhereRaw('LOWER(offer.review_status) <> ?', ['rejected']);
                    });
            }, 'offer_count')
            ->selectSub(function (Builder $offers): void {
                $offers->from('catalog_distributor_offers as rejected_offer')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('rejected_offer.product_id', 'cps.product_id')
                    ->where('rejected_offer.distributor', 'LCSC/JLCPCB')
                    ->whereColumn('rejected_offer.sku', 'cps.source_part_id')
                    ->whereRaw('LOWER(rejected_offer.review_status) = ?', ['rejected']);
            }, 'rejected_offer_count')
            ->selectSub(function (Builder $prices) use ($marketplaceCode, $currency): void {
                $prices->from('marketplace_product_prices as price')
                    ->join('marketplaces as marketplace', 'marketplace.id', '=', 'price.marketplace_id')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('price.product_id', 'cps.product_id')
                    ->where('price.is_active', true)
                    ->whereRaw('UPPER(marketplace.code) = ?', [$marketplaceCode])
                    ->whereRaw('UPPER(price.currency_code) = ?', [$currency]);
            }, 'price_count')
            ->selectSub(function (Builder $images): void {
                $images->from('product_images as image')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('image.product_id', 'cps.product_id')
                    ->where('image.is_active', true)
                    ->whereNotNull('image.file_path')
                    ->whereRaw("TRIM(image.file_path) <> ''")
                    ->whereRaw("LOWER(TRIM(image.file_path)) NOT LIKE 'http://%'")
                    ->whereRaw("LOWER(TRIM(image.file_path)) NOT LIKE 'https://%'")
                    ->whereRaw("TRIM(image.file_path) NOT LIKE '//%'");
            }, 'safe_image_count')
            ->selectSub(function (Builder $otherSources): void {
                $otherSources->from('catalog_product_sources as other_source')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('other_source.product_id', 'cps.product_id')
                    ->whereColumn('other_source.id', '<>', 'cps.id')
                    ->where(function (Builder $review): void {
                        $review->whereNull('other_source.review_status')
                            ->orWhereRaw('LOWER(other_source.review_status) <> ?', ['approved']);
                    });
            }, 'other_unapproved_source_count');

        if ($sourceLinkIds !== null) {
            $query->whereIn('cps.id', $sourceLinkIds);
        }

        return $query;
    }

    /** @return list<string> */
    private function blockersFor(object $row, float $minimumQuality): array
    {
        $reasons = [];
        if (in_array(strtolower((string) $row->source_review_status), ['rejected'], true)
            || in_array(strtolower((string) $row->product_status), ['rejected'], true)
            || in_array(strtolower((string) $row->approval_status), ['rejected'], true)
            || (int) $row->rejected_offer_count > 0) {
            $reasons[] = 'rejected_decision';
        }

        $manufacturerIdentity = $row->manufacturer_id !== null
            || $row->brand_id !== null
            || trim((string) $row->manufacturer_name) !== '';
        if (trim((string) $row->name) === ''
            || trim((string) $row->slug) === ''
            || trim((string) $row->sku) === ''
            || trim((string) $row->mpn) === ''
            || $row->category_id === null
            || ! $manufacturerIdentity) {
            $reasons[] = 'incomplete_identity';
        }
        if ((int) $row->has_raw_snapshot !== 1) {
            $reasons[] = 'missing_raw_snapshot';
        }
        if ((int) $row->offer_count < 1) {
            $reasons[] = 'missing_distributor_offer';
        }
        if ((int) $row->price_count < 1) {
            $reasons[] = 'missing_global_usd_price';
        }
        if ((int) $row->safe_image_count < 1) {
            $reasons[] = 'missing_active_local_image';
        }
        if ((float) $row->data_quality_score < $minimumQuality) {
            $reasons[] = 'below_minimum_data_quality';
        }
        if ((int) $row->other_unapproved_source_count > 0) {
            $reasons[] = 'other_unapproved_source';
        }
        if (! in_array((string) $row->visibility_status, ['hidden', 'public', 'marketplace_only', 'quote_only'], true)) {
            $reasons[] = 'unsupported_visibility';
        }

        return $reasons;
    }

    /**
     * @param  list<array<string, mixed>>  $chunk
     * @param  array<string, mixed>  $backup
     * @return array<string, int>
     */
    private function applyChunk(array $chunk, string $planHash, array $backup): array
    {
        $batch = [
            'changed_products' => 0,
            'unchanged_products' => 0,
            'source_reviews_approved' => 0,
            'offer_reviews_approved' => 0,
            'product_rows_updated' => 0,
            'batches_committed' => 1,
        ];
        $sourceLinkIds = array_map(static fn (array $row): int => (int) $row['source_link_id'], $chunk);
        $productIds = array_map(static fn (array $row): int => (int) $row['product_id'], $chunk);
        $sources = DB::table('catalog_product_sources')->whereIn('id', $sourceLinkIds)->get()->keyBy('id');
        $products = DB::table('products')->whereIn('id', $productIds)->get()->keyBy('id');
        if ($sources->count() !== count($sourceLinkIds) || $products->count() !== count($productIds)) {
            throw new RuntimeException('A qualified source link or product disappeared during bounded publication apply.');
        }

        $now = now();
        $sourceIdsToApprove = [];
        $sourceChangedProducts = [];
        $sourcePartByProduct = [];
        foreach ($chunk as $entry) {
            $source = $sources->get((int) $entry['source_link_id']);
            $productId = (int) $entry['product_id'];
            $sourcePartByProduct[$productId] = (string) $source->source_part_id;
            if ((string) $source->review_status !== 'approved') {
                $sourceIdsToApprove[] = (int) $source->id;
                $sourceChangedProducts[$productId] = true;
            }
        }
        if ($sourceIdsToApprove !== []) {
            $batch['source_reviews_approved'] = DB::table('catalog_product_sources')
                ->whereIn('id', $sourceIdsToApprove)
                ->update(['review_status' => 'approved', 'last_synced_at' => $now, 'updated_at' => $now]);
        }

        $offerIdsToApprove = [];
        $offerChangedProducts = [];
        $offers = DB::table('catalog_distributor_offers')
            ->whereIn('product_id', $productIds)
            ->where('distributor', 'LCSC/JLCPCB')
            ->where(function (Builder $review): void {
                $review->whereNull('review_status')
                    ->orWhere(function (Builder $notFinal): void {
                        $notFinal->whereRaw('LOWER(review_status) <> ?', ['approved'])
                            ->whereRaw('LOWER(review_status) <> ?', ['rejected']);
                    });
            })
            ->get(['id', 'product_id', 'sku']);
        foreach ($offers as $offer) {
            $productId = (int) $offer->product_id;
            if ((string) $offer->sku === ($sourcePartByProduct[$productId] ?? null)) {
                $offerIdsToApprove[] = (int) $offer->id;
                $offerChangedProducts[$productId] = true;
            }
        }
        if ($offerIdsToApprove !== []) {
            $batch['offer_reviews_approved'] = DB::table('catalog_distributor_offers')
                ->whereIn('id', $offerIdsToApprove)
                ->update(['review_status' => 'approved', 'updated_at' => $now]);
        }

        $audit = [
            'source_notes' => (string) config('jlcpcb_qualified_publication.audit.source_notes'),
            'confidence_level' => (string) config('jlcpcb_qualified_publication.audit.confidence_level'),
            'last_updated' => $now->toIso8601String(),
            'advisory_disclaimer' => (string) config('jlcpcb_qualified_publication.audit.advisory_disclaimer', 'Advisory only'),
            'source_code' => (string) config('jlcpcb_qualified_publication.source_code'),
            'plan_hash' => $planHash,
            'backup_manifest_sha256' => (string) $backup['manifest_sha256'],
        ];
        $productIdsToUpdate = [];
        foreach ($chunk as $entry) {
            $productId = (int) $entry['product_id'];
            $product = $products->get($productId);
            $metadata = $this->jsonArray($product->metadata);
            $productNeedsUpdate = ! in_array((string) $product->status, ['approved', 'active', 'published'], true)
                || (string) $product->approval_status !== 'approved'
                || (string) $product->visibility_status === 'hidden'
                || ! array_key_exists(self::AUDIT_KEY, $metadata);
            if ($productNeedsUpdate) {
                $productIdsToUpdate[] = $productId;
            }

            if ($productNeedsUpdate || isset($sourceChangedProducts[$productId]) || isset($offerChangedProducts[$productId])) {
                $batch['changed_products']++;
            } else {
                $batch['unchanged_products']++;
            }
        }

        if ($productIdsToUpdate !== []) {
            $this->bulkUpdateProducts($productIdsToUpdate, $products, $audit, $now);
            $batch['product_rows_updated'] = count($productIdsToUpdate);
        }

        return $batch;
    }

    /** @param list<int> $productIds @param array<string, mixed> $audit */
    private function bulkUpdateProducts(array $productIds, $products, array $audit, $now): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            foreach ($productIds as $productId) {
                $product = $products->get($productId);
                $metadata = $this->jsonArray($product->metadata);
                $metadata[self::AUDIT_KEY] ??= $audit;
                DB::table('products')->where('id', $productId)->update([
                    'status' => in_array((string) $product->status, ['approved', 'active', 'published'], true) ? $product->status : 'approved',
                    'approval_status' => 'approved',
                    'approved_at' => $product->approved_at ?: $now,
                    'visibility_status' => (string) $product->visibility_status === 'hidden' ? 'marketplace_only' : $product->visibility_status,
                    'metadata' => $this->json($metadata),
                    'updated_at' => $now,
                ]);
            }

            return;
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = <<<SQL
            UPDATE products
            SET status = CASE WHEN status IN ('approved', 'active', 'published') THEN status ELSE 'approved' END,
                approval_status = 'approved',
                approved_at = CASE WHEN approval_status IS DISTINCT FROM 'approved' AND approved_at IS NULL THEN ? ELSE approved_at END,
                visibility_status = CASE WHEN visibility_status = 'hidden' THEN 'marketplace_only' ELSE visibility_status END,
                metadata = CASE
                    WHEN NOT jsonb_exists(COALESCE(metadata::jsonb, '{}'::jsonb), ?)
                    THEN (COALESCE(metadata::jsonb, '{}'::jsonb) || jsonb_build_object(CAST(? AS text), CAST(? AS jsonb)))::json
                    ELSE metadata
                END,
                updated_at = ?
            WHERE id IN ({$placeholders})
            SQL;
        DB::update($sql, [
            $now,
            self::AUDIT_KEY,
            self::AUDIT_KEY,
            $this->json($audit),
            $now,
            ...$productIds,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $backup
     * @param  array<string, int>  $batch
     */
    private function applyRow(array $row, string $planHash, array $backup, array &$batch): void
    {
        $sourceLinkId = (int) $row['source_link_id'];
        $productId = (int) $row['product_id'];
        $now = now();
        $source = DB::table('catalog_product_sources')->where('id', $sourceLinkId)->first();
        $product = DB::table('products')->where('id', $productId)->first();
        if (! $source || ! $product) {
            throw new RuntimeException("Qualified source link {$sourceLinkId} or product {$productId} disappeared during apply.");
        }

        $changed = false;
        if ((string) $source->review_status !== 'approved') {
            DB::table('catalog_product_sources')->where('id', $sourceLinkId)->update([
                'review_status' => 'approved',
                'last_synced_at' => $now,
                'updated_at' => $now,
            ]);
            $batch['source_reviews_approved']++;
            $changed = true;
        }

        $offerReviews = DB::table('catalog_distributor_offers')
            ->where('product_id', $productId)
            ->where('distributor', 'LCSC/JLCPCB')
            ->where('sku', (string) $source->source_part_id)
            ->where(function (Builder $review): void {
                $review->whereNull('review_status')
                    ->orWhere(function (Builder $notFinal): void {
                        $notFinal->whereRaw('LOWER(review_status) <> ?', ['approved'])
                            ->whereRaw('LOWER(review_status) <> ?', ['rejected']);
                    });
            })
            ->update(['review_status' => 'approved', 'updated_at' => $now]);
        if ($offerReviews > 0) {
            $batch['offer_reviews_approved'] += $offerReviews;
            $changed = true;
        }

        $productUpdate = [];
        if (! in_array((string) $product->status, ['approved', 'active', 'published'], true)) {
            $productUpdate['status'] = 'approved';
        }
        if ((string) $product->approval_status !== 'approved') {
            $productUpdate['approval_status'] = 'approved';
            if ($product->approved_at === null) {
                $productUpdate['approved_at'] = $now;
            }
        }
        if ((string) $product->visibility_status === 'hidden') {
            $productUpdate['visibility_status'] = 'marketplace_only';
        }

        if ($productUpdate !== []) {
            $changed = true;
        }

        $metadata = $this->jsonArray($product->metadata);
        if (! array_key_exists(self::AUDIT_KEY, $metadata)) {
            $metadata[self::AUDIT_KEY] = [
                'source_notes' => (string) config('jlcpcb_qualified_publication.audit.source_notes'),
                'confidence_level' => (string) config('jlcpcb_qualified_publication.audit.confidence_level'),
                'last_updated' => $now->toIso8601String(),
                'advisory_disclaimer' => (string) config('jlcpcb_qualified_publication.audit.advisory_disclaimer', 'Advisory only'),
                'source_code' => (string) config('jlcpcb_qualified_publication.source_code'),
                'plan_hash' => $planHash,
                'backup_manifest_sha256' => (string) $backup['manifest_sha256'],
            ];
            $productUpdate['metadata'] = $this->json($metadata);
            $changed = true;
        }

        if ($productUpdate !== []) {
            $productUpdate['updated_at'] = $now;
            DB::table('products')->where('id', $productId)->update($productUpdate);
            $batch['product_rows_updated']++;
        }

        if ($changed) {
            $batch['changed_products']++;
        } else {
            $batch['unchanged_products']++;
        }
    }

    /** @return array{directory:string,manifest_sha256:string,restore_verification_sha256:string,verified_files:int,status:string} */
    private function verifyBackupDirectory(string $directory): array
    {
        $directory = trim($directory);
        $root = $directory === '' ? false : realpath($directory);
        if ($root === false || ! is_dir($root)) {
            throw new RuntimeException('--backup-dir must identify an existing verified backup directory.');
        }

        $configuredRoot = realpath((string) config('jlcpcb_qualified_publication.backup_root'));
        if ($configuredRoot === false || ! is_dir($configuredRoot)) {
            throw new RuntimeException('The configured JLCPCB_BACKUP_ROOT does not exist.');
        }
        if ($root === $configuredRoot || ! str_starts_with($root, $configuredRoot.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('--backup-dir must be a child directory of the configured JLCPCB_BACKUP_ROOT.');
        }

        $manifestPath = $root.DIRECTORY_SEPARATOR.'MANIFEST.txt';
        $checksumsPath = $root.DIRECTORY_SEPARATOR.'SHA256SUMS';
        $restoreVerificationPath = $root.DIRECTORY_SEPARATOR.'RESTORE_VERIFICATION.txt';
        $restoreChecksumsPath = $root.DIRECTORY_SEPARATOR.'RESTORE_VERIFICATION_SHA256SUMS';
        if (! is_file($manifestPath) || ! is_file($checksumsPath)
            || ! is_file($restoreVerificationPath) || ! is_file($restoreChecksumsPath)) {
            throw new RuntimeException('The backup directory must contain MANIFEST.txt, SHA256SUMS, RESTORE_VERIFICATION.txt, and RESTORE_VERIFICATION_SHA256SUMS.');
        }

        $manifest = file_get_contents($manifestPath);
        if ($manifest === false || ! $this->manifestIsVerified($manifest)) {
            throw new RuntimeException('The backup MANIFEST.txt must declare status=verified.');
        }

        $restoreVerification = file_get_contents($restoreVerificationPath);
        if ($restoreVerification === false
            || preg_match('/(?:^|\R)\s*result\s*=\s*passed\s*(?:$|\R)/i', $restoreVerification) !== 1) {
            throw new RuntimeException('RESTORE_VERIFICATION.txt must declare result=passed.');
        }

        $mainVerified = $this->verifyChecksumFile($root, $checksumsPath, 'MANIFEST.txt');
        $restoreVerified = $this->verifyChecksumFile($root, $restoreChecksumsPath, 'RESTORE_VERIFICATION.txt');

        return [
            'directory' => $root,
            'manifest_sha256' => hash('sha256', $manifest),
            'restore_verification_sha256' => hash('sha256', $restoreVerification),
            'verified_files' => $mainVerified + $restoreVerified,
            'status' => 'verified',
        ];
    }

    private function verifyChecksumFile(string $root, string $checksumsPath, string $requiredFile): int
    {
        $checksumContents = file_get_contents($checksumsPath);
        if ($checksumContents === false) {
            throw new RuntimeException(basename($checksumsPath).' could not be read.');
        }
        $verified = 0;
        $requiredCovered = false;
        foreach (preg_split('/\R/', $checksumContents) ?: [] as $lineNumber => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (preg_match('/^([a-f0-9]{64})[ \t]+\*?(.+)$/i', $line, $matches) !== 1) {
                throw new RuntimeException('Malformed SHA256SUMS entry on line '.($lineNumber + 1).'.');
            }

            $expected = strtolower($matches[1]);
            $relative = $this->safeRelativePath(trim($matches[2]));
            $candidate = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $real = realpath($candidate);
            if ($real === false || ! is_file($real) || ($real !== $root && ! str_starts_with($real, $root.DIRECTORY_SEPARATOR))) {
                throw new RuntimeException("SHA256SUMS references a missing or unsafe backup file: {$relative}.");
            }
            $actual = hash_file('sha256', $real);
            if (! is_string($actual) || ! hash_equals($expected, strtolower($actual))) {
                throw new RuntimeException("Backup checksum verification failed for {$relative}.");
            }

            $verified++;
            $requiredCovered = $requiredCovered || $relative === $requiredFile;
        }

        if ($verified < 1 || ! $requiredCovered) {
            throw new RuntimeException(basename($checksumsPath)." must verify at least one file and must cover {$requiredFile}.");
        }

        return $verified;
    }

    private function manifestIsVerified(string $manifest): bool
    {
        $decoded = json_decode($manifest, true);
        if (is_array($decoded) && strtolower(trim((string) ($decoded['status'] ?? ''))) === 'verified') {
            return true;
        }

        return preg_match('/(?:^|\R)\s*status\s*[:=]\s*["\']?verified["\']?\s*(?:$|\R)/i', $manifest) === 1;
    }

    private function safeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, "\0")) {
            throw new RuntimeException('SHA256SUMS contains an unsafe file path.');
        }

        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                throw new RuntimeException('SHA256SUMS contains a path traversal entry.');
            }
            $parts[] = $part;
        }
        if ($parts === []) {
            throw new RuntimeException('SHA256SUMS contains an empty file path.');
        }

        return implode('/', $parts);
    }

    private function minimumQuality(?float $minimumQuality): float
    {
        $quality = $minimumQuality ?? (float) config('jlcpcb_qualified_publication.minimum_data_quality_score', 0.65);
        if (! is_finite($quality) || $quality < 0 || $quality > 1) {
            throw new RuntimeException('The minimum data-quality score must be between 0 and 1.');
        }

        return round($quality, 4);
    }

    private function batchSize(?int $batchSize): int
    {
        $batchSize ??= (int) config('jlcpcb_qualified_publication.batch_size', 250);
        $maximum = (int) config('jlcpcb_qualified_publication.maximum_batch_size', 1_000);
        if ($batchSize < 1 || $batchSize > $maximum) {
            throw new RuntimeException("The bounded batch size must be between 1 and {$maximum}.");
        }

        return $batchSize;
    }

    private function assertSchema(): void
    {
        $required = [
            'products' => ['id', 'name', 'slug', 'sku', 'mpn', 'category_id', 'brand_id', 'manufacturer_id', 'manufacturer_name', 'status', 'approval_status', 'visibility_status', 'metadata', 'approved_at'],
            'catalog_sources' => ['id', 'code'],
            'catalog_product_sources' => ['id', 'product_id', 'source_id', 'source_part_id', 'source_payload_hash', 'data_quality_score', 'review_status', 'raw_snapshot', 'last_synced_at'],
            'catalog_distributor_offers' => ['id', 'product_id', 'review_status'],
            'marketplaces' => ['id', 'code'],
            'marketplace_product_prices' => ['id', 'product_id', 'marketplace_id', 'currency_code', 'is_active'],
            'product_images' => ['id', 'product_id', 'file_path', 'is_active'],
        ];

        foreach ($required as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required qualified-publication table {$table} is missing.");
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException("Required qualified-publication column {$table}.{$column} is missing.");
                }
            }
        }
    }

    /** @return array<string, mixed> */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }
}
