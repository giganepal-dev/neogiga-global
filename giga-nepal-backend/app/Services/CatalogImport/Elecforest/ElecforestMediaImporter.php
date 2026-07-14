<?php

namespace App\Services\CatalogImport\Elecforest;

use App\Jobs\CatalogImport\GenerateElecforestImageDerivativesJob;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ElecforestMediaImporter
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];

    public function __construct(private readonly ElecforestRecordParser $parser) {}

    /** @return array<string, mixed> */
    public function downloadAsset(int $assetId): array
    {
        $asset = DB::table('supplier_product_assets as a')
            ->join('supplier_products as sp', 'sp.id', '=', 'a.supplier_product_id')
            ->join('products as p', 'p.id', '=', 'sp.product_id')
            ->where('a.id', $assetId)
            ->select(['a.*', 'sp.product_id', 'sp.source_url as source_product_url', 'p.name as product_name'])
            ->first();
        if (! $asset || ! $asset->product_id) {
            throw new \RuntimeException("ElecForest media asset {$assetId} is not linked to a product.");
        }
        if (! $this->parser->isProductImageUrl((string) $asset->original_url)) {
            DB::table('supplier_product_assets')->where('id', $assetId)->update([
                'download_status' => 'ignored_non_product_asset', 'rights_status' => 'not_applicable', 'updated_at' => now(),
            ]);

            return ['status' => 'ignored_non_product_asset', 'asset_id' => $assetId];
        }
        if ($asset->download_status === 'downloaded' && $asset->local_path && Storage::disk(config('elecforest_import.image_disk'))->exists($asset->local_path)) {
            return ['status' => 'unchanged', 'asset_id' => $assetId, 'local_path' => $asset->local_path];
        }

        DB::table('supplier_product_assets')->where('id', $assetId)->update(['download_status' => 'downloading', 'updated_at' => now()]);
        try {
            [$body, $finalUrl, $headerMime] = $this->fetch((string) $asset->original_url);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($body) ?: '';
            if (! in_array($mime, self::ALLOWED_MIMES, true)) {
                throw new \RuntimeException("Rejected image MIME type [{$mime}].");
            }
            if ($headerMime !== '' && ! str_starts_with($headerMime, 'image/')) {
                throw new \RuntimeException("Remote Content-Type is not an image [{$headerMime}].");
            }
            $dimensions = @getimagesizefromstring($body);
            if (! is_array($dimensions)) {
                throw new \RuntimeException('Image signature or dimensions could not be verified.');
            }
            [$width, $height] = [(int) $dimensions[0], (int) $dimensions[1]];
            $min = (int) config('elecforest_import.min_image_dimension');
            $max = (int) config('elecforest_import.max_image_dimension');
            if ($width < $min || $height < $min || $width > $max || $height > $max) {
                throw new \RuntimeException("Rejected image dimensions {$width}x{$height}.");
            }

            $checksum = hash('sha256', $body);
            $extension = match ($mime) {
                'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
                'image/gif' => 'gif', 'image/avif' => 'avif', default => throw new \RuntimeException('Unsupported image format.'),
            };
            $path = trim((string) config('elecforest_import.image_directory'), '/').'/'.substr($checksum, 0, 2).'/'.$checksum.'.'.$extension;
            Storage::disk(config('elecforest_import.image_disk'))->put($path, $body);

            DB::transaction(function () use ($asset, $assetId, $path, $mime, $checksum, $width, $height, $body, $finalUrl): void {
                DB::table('supplier_product_assets')->where('id', $assetId)->update([
                    'canonical_url' => $finalUrl, 'local_path' => $path, 'mime_type' => $mime,
                    'checksum' => $checksum, 'size_bytes' => strlen($body), 'download_status' => 'downloaded',
                    'retrieved_at' => now(), 'rights_status' => 'pending_review', 'updated_at' => now(),
                ]);
                $existing = DB::table('product_images')->where('product_id', $asset->product_id)->where('checksum', $checksum)->first();
                DB::table('product_images')->updateOrInsert(
                    ['product_id' => $asset->product_id, 'checksum' => $checksum],
                    [
                        'file_path' => $path, 'file_name' => basename($path), 'mime_type' => $mime,
                        'file_size' => strlen($body), 'sort_order' => $asset->sort_order, 'is_primary' => (int) $asset->sort_order === 0,
                        'alt_text' => $asset->alt_text ?: Str::limit($asset->product_name.' product image', 250, ''),
                        'caption' => Str::limit('Source product image for '.$asset->product_name.'; redistribution rights pending administrator review.', 500, ''),
                        'is_active' => false, 'original_url' => $asset->original_url, 'source_url' => $finalUrl,
                        'source_name' => 'ElecForest', 'source_license' => 'pending rights review',
                        'copyright' => 'Rights not verified; internal review only.', 'width' => $width, 'height' => $height,
                        'downloaded_at' => now(), 'metadata' => json_encode([
                            'source_notes' => 'Downloaded from the source image URL and held inactive until media-rights approval.',
                            'source_page_url' => $asset->source_product_url,
                            'title' => Str::limit($asset->product_name.' product image', 250, ''),
                            'confidence_level' => 'source_asset_unverified_rights', 'last_updated' => now()->toIso8601String(),
                            'advisory_disclaimer' => config('elecforest_import.advisory_disclaimer'),
                        ]),
                        'created_at' => $existing->created_at ?? now(), 'updated_at' => now(),
                    ]
                );
            });
            $imageId = (int) DB::table('product_images')->where('product_id', $asset->product_id)->where('checksum', $checksum)->value('id');
            GenerateElecforestImageDerivativesJob::dispatch($imageId)->onQueue((string) config('elecforest_import.derivative_queue'));

            return compact('assetId', 'path', 'mime', 'width', 'height', 'checksum') + ['status' => 'downloaded', 'asset_id' => $assetId];
        } catch (\Throwable $exception) {
            DB::table('supplier_product_assets')->where('id', $assetId)->update([
                'download_status' => 'failed', 'updated_at' => now(),
            ]);
            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public function generateDerivatives(int $imageId): array
    {
        $image = DB::table('product_images')->find($imageId);
        if (! $image) {
            throw new \RuntimeException("Product image {$imageId} was not found.");
        }
        $disk = Storage::disk(config('elecforest_import.image_disk'));
        if (! $disk->exists($image->file_path)) {
            throw new \RuntimeException("Product image file {$image->file_path} was not found.");
        }
        $base = preg_replace('/\.[^.]+$/', '', $image->file_path) ?: $image->file_path;
        $sourceWidth = (int) ($image->width ?? 0);
        if ($sourceWidth > 0) {
            $preGenerated = [];
            $complete = true;
            foreach (array_values(array_unique(array_map(
                static fn (int $maxWidth): int => min($sourceWidth, $maxWidth),
                [160, 400, 800, 1200]
            ))) as $targetWidth) {
                foreach (['webp', 'avif'] as $format) {
                    $path = $base.'-'.$targetWidth.'w.'.$format;
                    if (! $disk->exists($path)) {
                        $complete = false;
                        break 2;
                    }
                    $preGenerated[$format.'_'.$targetWidth] = $path;
                }
            }
            if ($complete && $preGenerated !== []) {
                $metadata = json_decode((string) $image->metadata, true) ?: [];
                $metadata['derivatives'] = $preGenerated;
                $metadata['last_updated'] = now()->toIso8601String();
                DB::table('product_images')->where('id', $imageId)->update(['metadata' => json_encode($metadata), 'updated_at' => now()]);

                return ['status' => 'reused', 'image_id' => $imageId, 'derivatives' => $preGenerated];
            }
        }
        if (! function_exists('imagecreatefromstring')) {
            return ['status' => 'skipped', 'reason' => 'GD is unavailable'];
        }
        $resource = @imagecreatefromstring($disk->get($image->file_path));
        if ($resource === false) {
            throw new \RuntimeException('GD could not decode the source image.');
        }
        $derivatives = [];
        $renderedWidths = [];
        foreach ([160, 400, 800, 1200] as $maxWidth) {
            $width = imagesx($resource);
            $height = imagesy($resource);
            $targetWidth = min($width, $maxWidth);
            if (isset($renderedWidths[$targetWidth])) {
                continue;
            }
            $renderedWidths[$targetWidth] = true;
            $targetHeight = max(1, (int) round($height * ($targetWidth / $width)));
            $target = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagecopyresampled($target, $resource, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            if (function_exists('imagewebp')) {
                $path = $base.'-'.$targetWidth.'w.webp';
                if (! $disk->exists($path)) {
                    ob_start(); imagewebp($target, null, 82); $bytes = ob_get_clean();
                    if (is_string($bytes)) {
                        $disk->put($path, $bytes);
                    }
                }
                if ($disk->exists($path)) {
                    $derivatives['webp_'.$targetWidth] = $path;
                }
            }
            if (function_exists('imageavif')) {
                $path = $base.'-'.$targetWidth.'w.avif';
                if (! $disk->exists($path)) {
                    ob_start(); imageavif($target, null, 65, 8); $bytes = ob_get_clean();
                    if (is_string($bytes)) {
                        $disk->put($path, $bytes);
                    }
                }
                if ($disk->exists($path)) {
                    $derivatives['avif_'.$targetWidth] = $path;
                }
            }
            imagedestroy($target);
        }
        imagedestroy($resource);
        $metadata = json_decode((string) $image->metadata, true) ?: [];
        $metadata['derivatives'] = $derivatives;
        $metadata['last_updated'] = now()->toIso8601String();
        DB::table('product_images')->where('id', $imageId)->update(['metadata' => json_encode($metadata), 'updated_at' => now()]);

        return ['status' => 'generated', 'image_id' => $imageId, 'derivatives' => $derivatives];
    }

    /** @return array{0:string,1:string,2:string} */
    private function fetch(string $url): array
    {
        $redirects = 0;
        while (true) {
            $this->assertSafeUrl($url);
            $response = Http::withOptions(['stream' => true, 'allow_redirects' => false])
                ->connectTimeout((int) config('elecforest_import.image_connect_timeout'))
                ->timeout((int) config('elecforest_import.image_timeout'))
                ->withHeaders(['User-Agent' => 'NeoGigaCatalogImporter/1.0', 'Accept' => 'image/avif,image/webp,image/png,image/jpeg,image/gif'])
                ->get($url);
            if (in_array($response->status(), [301, 302, 303, 307, 308], true)) {
                if (++$redirects > (int) config('elecforest_import.image_redirects')) {
                    throw new \RuntimeException('Image redirect limit exceeded.');
                }
                $url = $this->redirectUrl($url, (string) $response->header('Location'));
                continue;
            }
            if (! $response->successful()) {
                throw new \RuntimeException("Image request failed with HTTP {$response->status()}.");
            }

            return [$this->boundedBody($response), $url, strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]))];
        }
    }

    private function boundedBody(Response $response): string
    {
        $limit = (int) config('elecforest_import.max_image_bytes');
        $length = (int) ($response->header('Content-Length') ?: 0);
        if ($length > $limit) {
            throw new \RuntimeException("Image exceeds the {$limit}-byte limit.");
        }
        $stream = $response->toPsrResponse()->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $body = '';
        while (! $stream->eof()) {
            $body .= $stream->read(65536);
            if (strlen($body) > $limit) {
                throw new \RuntimeException("Image exceeds the {$limit}-byte limit.");
            }
        }
        if ($body === '') {
            throw new \RuntimeException('Image response was empty.');
        }

        return $body;
    }

    private function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (($parts['scheme'] ?? '') !== 'https' || $host === '' || ! in_array($host, config('elecforest_import.allowed_image_hosts'), true)) {
            throw new \RuntimeException('Image URL is not an allowlisted HTTPS URL.');
        }
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            throw new \RuntimeException('Image URL credentials and custom ports are forbidden.');
        }
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : array_values(array_unique(array_filter([
            ...array_map(static fn (array $record): ?string => $record['ip'] ?? null, dns_get_record($host, DNS_A) ?: []),
            ...array_map(static fn (array $record): ?string => $record['ipv6'] ?? null, dns_get_record($host, DNS_AAAA) ?: []),
        ])));
        if ($ips === []) {
            throw new \RuntimeException('Image host did not resolve to a public address.');
        }
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new \RuntimeException("Image host resolved to a forbidden address [{$ip}].");
            }
        }
    }

    private function redirectUrl(string $base, string $location): string
    {
        if ($location === '') {
            throw new \RuntimeException('Image redirect omitted the Location header.');
        }
        if (str_starts_with($location, 'https://')) {
            return $location;
        }
        $parts = parse_url($base);
        if (str_starts_with($location, '/')) {
            return 'https://'.$parts['host'].$location;
        }
        $directory = rtrim(dirname((string) ($parts['path'] ?? '/')), '/');

        return 'https://'.$parts['host'].$directory.'/'.$location;
    }
}
