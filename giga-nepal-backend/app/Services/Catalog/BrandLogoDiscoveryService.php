<?php

namespace App\Services\Catalog;

use App\Models\Marketplace\BrandLogoHistory;
use App\Models\Marketplace\ProductBrand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BrandLogoDiscoveryService
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif', 'image/svg+xml'];

    public function normalizeBrandName(?string $name): string
    {
        return app(BrandIdentityResolver::class)->normalizeBrandName($name);
    }

    public function resolveOfficialDomain(ProductBrand $brand): ?string
    {
        $configured = config('brand_logos.official_domains.'.$brand->slug);
        if (is_string($configured) && $configured !== '') {
            return strtolower($configured);
        }

        $host = parse_url((string) $brand->website_url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        return strtolower(preg_replace('/^www\./', '', $host));
    }

    /** @return array<string, mixed> */
    public function discoverOfficialLogo(ProductBrand $brand): array
    {
        $officialDomain = $this->resolveOfficialDomain($brand);
        $website = $this->websiteUrl($brand, $officialDomain);
        if (! $officialDomain || ! $website || ! $this->isSafeRemoteUrl($website)) {
            return $this->manualPlan($brand, $officialDomain, 'No safe official website URL is configured for this brand.');
        }

        try {
            $response = Http::timeout(12)
                ->connectTimeout(5)
                ->withHeaders(['User-Agent' => 'NeoGigaBrandLogoVerifier/1.0', 'Accept' => 'text/html,application/xhtml+xml'])
                ->get($website);
        } catch (\Throwable $exception) {
            return $this->manualPlan($brand, $officialDomain, 'Official website could not be reached: '.$exception->getMessage());
        }

        if (! $response->successful() || ! str_contains(strtolower((string) $response->header('Content-Type')), 'html')) {
            return $this->manualPlan($brand, $officialDomain, 'Official website did not return an HTML page suitable for logo discovery.');
        }

        $candidates = $this->extractCandidates($response->body(), $website);
        foreach ($candidates as $candidate) {
            $validation = $this->validateLogoMatch($brand, $officialDomain, $candidate);
            if ($validation['acceptable']) {
                return array_merge($validation, [
                    'brand_id' => $brand->id,
                    'brand_name' => $brand->name,
                    'official_domain' => $officialDomain,
                    'source_page_url' => $website,
                    'action' => $validation['confidence'] >= config('brand_logos.minimum_auto_accept_confidence') ? 'stage_for_approval' : 'manual_review',
                ]);
            }
        }

        return $this->manualPlan($brand, $officialDomain, 'No suitable non-favicon logo candidate was found on the official website.');
    }

    /** @return array<string, mixed> */
    public function validateLogoMatch(ProductBrand $brand, string $officialDomain, array $candidate): array
    {
        $url = (string) ($candidate['url'] ?? '');
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $sameDomain = $this->hostMatches($host, $officialDomain);
        $filename = strtolower((string) pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_BASENAME));
        $context = strtolower(implode(' ', [(string) ($candidate['alt'] ?? ''), (string) ($candidate['source'] ?? ''), $filename]));
        $normalizedBrand = $this->normalizeBrandName($brand->name);
        $containsBrand = $normalizedBrand !== '' && str_contains($this->normalizeBrandName($context), $normalizedBrand);
        $isLogo = str_contains($context, 'logo') || str_contains($filename, 'logo');
        $isFavicon = str_contains($filename, 'favicon') || str_contains($filename, 'icon-');

        $confidence = 0.45 + ($sameDomain ? 0.25 : 0) + ($containsBrand ? 0.20 : 0) + ($isLogo ? 0.10 : 0);
        if ($isFavicon) {
            $confidence = 0;
        }

        return [
            'acceptable' => ! $isFavicon && $sameDomain && $this->isSafeRemoteUrl($url) && $confidence >= 0.60,
            'proposed_logo_url' => $url,
            'source_type' => $candidate['source_type'] ?? 'official_website',
            'confidence' => min(1, round($confidence, 3)),
            'review_note' => $isFavicon
                ? 'Rejected candidate because it is a favicon, not a brand logo.'
                : ($sameDomain ? 'Candidate was discovered on the configured official domain.' : 'Candidate requires manual domain review.'),
            'evidence' => $candidate,
        ];
    }

    public function stageDiscoveredLogo(ProductBrand $brand, array $plan, ?int $userId = null): BrandLogoHistory
    {
        if (($plan['action'] ?? null) !== 'stage_for_approval' || empty($plan['proposed_logo_url'])) {
            throw ValidationException::withMessages(['logo' => 'Only high-confidence official candidates can be staged.']);
        }

        $download = $this->downloadLogo((string) $plan['proposed_logo_url'], $brand->id, 'staging');

        return BrandLogoHistory::create([
            'brand_id' => $brand->id,
            'action' => 'discovered',
            'storage_disk' => $download['disk'],
            'logo_path' => $download['path'],
            'original_url' => $plan['proposed_logo_url'],
            'source_domain' => $plan['official_domain'] ?? parse_url((string) $plan['proposed_logo_url'], PHP_URL_HOST),
            'source_type' => $plan['source_type'] ?? 'official_website',
            'confidence' => $plan['confidence'] ?? null,
            'status' => 'pending',
            'review_note' => $plan['review_note'] ?? null,
            'metadata' => array_merge($this->metadataFor($download), ['evidence' => $plan['evidence'] ?? []]),
            'created_by' => $userId,
        ]);
    }

    public function stageManualUpload(ProductBrand $brand, UploadedFile $file, array $source, ?int $userId = null): BrandLogoHistory
    {
        $bytes = file_get_contents($file->getRealPath());
        if (! is_string($bytes)) {
            throw ValidationException::withMessages(['logo' => 'The uploaded logo could not be read.']);
        }
        $inspection = $this->inspectBytes($bytes, $file->getClientOriginalExtension());
        $disk = (string) config('brand_logos.disk', 'public');
        $path = 'brands/'.$brand->id.'/staging/'.hash('sha256', $bytes).'.'.$inspection['extension'];
        Storage::disk($disk)->put($path, $inspection['bytes']);

        return BrandLogoHistory::create([
            'brand_id' => $brand->id,
            'action' => 'manual_upload',
            'storage_disk' => $disk,
            'logo_path' => $path,
            'original_url' => $source['original_url'] ?? null,
            'source_domain' => $source['source_domain'] ?? null,
            'source_type' => 'manual_upload',
            'confidence' => $source['confidence'] ?? 0.5,
            'status' => 'pending',
            'review_note' => $source['review_note'] ?? 'Manual upload awaiting official-source verification.',
            'metadata' => $this->metadataFor($inspection),
            'created_by' => $userId,
        ]);
    }

    public function approveStagedLogo(ProductBrand $brand, BrandLogoHistory $history, int $userId): ProductBrand
    {
        if ($history->brand_id !== $brand->id || $history->status !== 'pending') {
            throw ValidationException::withMessages(['logo' => 'This logo candidate is not pending for the selected brand.']);
        }
        if (! $history->logo_path || ! Storage::disk($history->storage_disk ?: config('brand_logos.disk'))->exists($history->logo_path)) {
            throw ValidationException::withMessages(['logo' => 'The staged logo file is unavailable.']);
        }
        if ($brand->logo_verified && (float) ($history->confidence ?? 0) < (float) ($brand->logo_confidence ?? 1)) {
            throw ValidationException::withMessages(['logo' => 'A lower-confidence candidate cannot replace a verified official logo.']);
        }

        $disk = (string) ($history->storage_disk ?: config('brand_logos.disk', 'public'));
        $bytes = Storage::disk($disk)->get($history->logo_path);
        $inspection = $this->inspectBytes($bytes, pathinfo($history->logo_path, PATHINFO_EXTENSION));
        $base = 'brands/'.$brand->id;
        $original = $base.'/logo-original.'.$inspection['extension'];
        Storage::disk($disk)->put($original, $inspection['bytes']);
        $variants = $this->createVariants($disk, $base, $inspection);

        $previous = $brand->only(['logo_path', 'logo_original_url', 'logo_sha256', 'logo_status']);
        $brand->update([
            'logo_path' => $variants['light'],
            'logo_original_url' => $history->original_url,
            'logo_source_domain' => $history->source_domain,
            'logo_source_type' => $history->source_type,
            'logo_verified' => true,
            'logo_verified_at' => now(),
            'logo_verified_by' => $userId,
            'logo_alt_text' => $brand->name.' official logo',
            'logo_width' => $inspection['width'],
            'logo_height' => $inspection['height'],
            'logo_mime_type' => $inspection['mime'],
            'logo_sha256' => $inspection['sha256'],
            'logo_background_type' => $inspection['background_type'],
            'logo_status' => 'verified',
            'logo_confidence' => $history->confidence,
            'logo_review_note' => 'Approved from a reviewed official-source candidate.',
            'logo_metadata' => array_merge($this->metadataFor($inspection), ['original_path' => $original, 'variants' => $variants]),
        ]);
        $history->update(['status' => 'approved', 'action' => 'approved', 'metadata' => array_merge($history->metadata ?? [], ['previous' => $previous, 'variants' => $variants])]);

        return $brand->fresh();
    }

    public function regenerateVariants(ProductBrand $brand): ProductBrand
    {
        $metadata = is_array($brand->logo_metadata) ? $brand->logo_metadata : [];
        $originalPath = $metadata['original_path'] ?? null;
        $disk = (string) config('brand_logos.disk', 'public');
        if (! $brand->logo_verified || ! is_string($originalPath) || ! Storage::disk($disk)->exists($originalPath)) {
            throw ValidationException::withMessages(['logo' => 'A verified stored original is required before variants can be regenerated.']);
        }

        $bytes = Storage::disk($disk)->get($originalPath);
        $inspection = $this->inspectBytes($bytes, pathinfo($originalPath, PATHINFO_EXTENSION));
        $variants = $this->createVariants($disk, 'brands/'.$brand->id, $inspection);
        $brand->update(['logo_path' => $variants['light'], 'logo_metadata' => array_merge($metadata, $this->metadataFor($inspection), ['variants' => $variants])]);

        return $brand->fresh();
    }

    /** @return array<string, mixed> */
    public function downloadLogo(string $url, int $brandId, string $folder = 'staging'): array
    {
        if (! $this->isSafeRemoteUrl($url)) {
            throw ValidationException::withMessages(['logo' => 'The logo URL is not a safe public HTTP(S) URL.']);
        }
        $response = Http::timeout(20)->connectTimeout(5)->withHeaders(['User-Agent' => 'NeoGigaBrandLogoVerifier/1.0'])->get($url);
        if (! $response->successful()) {
            throw ValidationException::withMessages(['logo' => 'The official logo URL could not be downloaded.']);
        }

        $inspection = $this->inspectBytes($response->body(), pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $disk = (string) config('brand_logos.disk', 'public');
        $path = 'brands/'.$brandId.'/'.$folder.'/'.$inspection['sha256'].'.'.$inspection['extension'];
        Storage::disk($disk)->put($path, $inspection['bytes']);

        return $inspection + ['disk' => $disk, 'path' => $path];
    }

    /** @return array<string, mixed> */
    private function inspectBytes(string $bytes, ?string $extension): array
    {
        if ($bytes === '' || strlen($bytes) > (int) config('brand_logos.max_download_bytes')) {
            throw ValidationException::withMessages(['logo' => 'Logo file is empty or exceeds the approved size limit.']);
        }

        $extension = strtolower((string) $extension);
        if ($extension === 'svg' || str_starts_with(ltrim($bytes), '<svg')) {
            if (preg_match('/<(script|foreignObject)|\bon\w+\s*=|<!ENTITY|javascript:/i', $bytes)) {
                throw ValidationException::withMessages(['logo' => 'SVG contains unsafe executable or embedded content.']);
            }
            if (! str_contains(strtolower($bytes), '<svg')) {
                throw ValidationException::withMessages(['logo' => 'Uploaded SVG is malformed.']);
            }
            return [
                'bytes' => $bytes, 'extension' => 'svg', 'mime' => 'image/svg+xml', 'width' => null, 'height' => null,
                'sha256' => hash('sha256', $bytes), 'background_type' => 'transparent',
            ];
        }

        $info = @getimagesizefromstring($bytes);
        $mime = $info['mime'] ?? null;
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages(['logo' => 'Logo must be a valid raster image or a safe SVG.']);
        }
        if ((int) ($info[0] ?? 0) < (int) config('brand_logos.min_width') || (int) ($info[1] ?? 0) < 16) {
            throw ValidationException::withMessages(['logo' => 'Logo resolution is too small for the brand directory.']);
        }
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif'];

        return [
            'bytes' => $bytes, 'extension' => $extensions[$mime] ?? $extension ?: 'bin', 'mime' => $mime,
            'width' => (int) $info[0], 'height' => (int) $info[1], 'sha256' => hash('sha256', $bytes),
            'background_type' => in_array($mime, ['image/png', 'image/webp', 'image/gif'], true) ? 'transparent' : 'unknown',
        ];
    }

    /** @return array{light: string, dark: string, square: string} */
    private function createVariants(string $disk, string $base, array $inspection): array
    {
        $original = $base.'/logo-original.'.$inspection['extension'];
        $fallback = ['light' => $original, 'dark' => $original, 'square' => $original];
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp') || $inspection['mime'] === 'image/svg+xml') {
            return $fallback;
        }

        $source = @imagecreatefromstring($inspection['bytes']);
        if (! $source) {
            return $fallback;
        }
        $variants = [];
        foreach (['light' => 320, 'dark' => 320, 'square' => 160] as $name => $max) {
            $width = imagesx($source);
            $height = imagesy($source);
            $ratio = min($max / $width, $max / $height, 1);
            $targetWidth = max(1, (int) round($width * $ratio));
            $targetHeight = max(1, (int) round($height * $ratio));
            $target = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
            imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            ob_start(); imagewebp($target, null, 84); $encoded = ob_get_clean();
            imagedestroy($target);
            if (! is_string($encoded) || $encoded === '') {
                continue;
            }
            $path = $base.'/logo-'.$name.'.webp';
            Storage::disk($disk)->put($path, $encoded);
            $variants[$name] = $path;
        }
        imagedestroy($source);

        return array_merge($fallback, $variants);
    }

    /** @return array<int, array<string, string>> */
    private function extractCandidates(string $html, string $baseUrl): array
    {
        $candidates = [];
        if (preg_match_all('/<script[^>]+type=["\']application\\/ld\\+json["\'][^>]*>(.*?)<\\/script>/is', $html, $scripts)) {
            foreach ($scripts[1] as $script) {
                $decoded = json_decode(trim($script), true);
                foreach ($this->logosFromJsonLd($decoded) as $url) {
                    $candidates[] = ['url' => $this->absoluteUrl($url, $baseUrl), 'source' => 'json-ld Organization.logo', 'source_type' => 'official_website'];
                }
            }
        }
        if (preg_match('/<meta[^>]+(?:property|name)=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $match)) {
            $candidates[] = ['url' => $this->absoluteUrl($match[1], $baseUrl), 'source' => 'OpenGraph image', 'source_type' => 'official_website'];
        }
        if (preg_match_all('/<img[^>]+>/i', $html, $images)) {
            foreach ($images[0] as $image) {
                if (! preg_match('/\bsrc=["\']([^"\']+)["\']/i', $image, $src)) {
                    continue;
                }
                preg_match('/\balt=["\']([^"\']*)["\']/i', $image, $alt);
                if (! str_contains(strtolower($image), 'logo') && ! str_contains(strtolower($alt[1] ?? ''), 'logo')) {
                    continue;
                }
                $candidates[] = ['url' => $this->absoluteUrl($src[1], $baseUrl), 'alt' => $alt[1] ?? '', 'source' => 'website header/footer image', 'source_type' => 'official_website'];
            }
        }

        return array_values(array_filter($candidates, fn (array $candidate) => ! empty($candidate['url'])));
    }

    /** @return array<int, string> */
    private function logosFromJsonLd(mixed $node): array
    {
        if (! is_array($node)) {
            return [];
        }
        $logos = [];
        if (isset($node['logo'])) {
            $logos[] = is_array($node['logo']) ? ($node['logo']['url'] ?? '') : $node['logo'];
        }
        foreach ($node as $value) {
            if (is_array($value)) {
                $logos = array_merge($logos, $this->logosFromJsonLd($value));
            }
        }

        return array_values(array_filter($logos, 'is_string'));
    }

    private function websiteUrl(ProductBrand $brand, ?string $domain): ?string
    {
        if ($brand->website_url && filter_var($brand->website_url, FILTER_VALIDATE_URL)) {
            return $brand->website_url;
        }

        return $domain ? 'https://'.$domain : null;
    }

    private function absoluteUrl(string $value, string $baseUrl): string
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        $parts = parse_url($baseUrl);
        if (! $parts || ! isset($parts['scheme'], $parts['host'])) {
            return '';
        }

        return $parts['scheme'].'://'.$parts['host'].'/'.ltrim($value, '/');
    }

    private function isSafeRemoteUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            || $host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }
        if (app()->environment('testing')) {
            return true;
        }
        $ips = gethostbynamel($host) ?: [];

        return $ips !== [] && collect($ips)->every(fn (string $ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false);
    }

    private function hostMatches(string $host, string $domain): bool
    {
        $host = preg_replace('/^www\./', '', $host);
        $domain = preg_replace('/^www\./', '', strtolower($domain));

        return $host === $domain || str_ends_with($host, '.'.$domain);
    }

    /** @param array<string, mixed> $inspection */
    private function metadataFor(array $inspection): array
    {
        return Arr::except($inspection, ['bytes']);
    }

    /** @return array<string, mixed> */
    private function manualPlan(ProductBrand $brand, ?string $domain, string $note): array
    {
        return [
            'brand_id' => $brand->id,
            'brand_name' => $brand->name,
            'official_domain' => $domain,
            'proposed_logo_url' => null,
            'source_type' => null,
            'confidence' => 0,
            'action' => 'manual_review',
            'review_note' => $note,
            'evidence' => [],
        ];
    }
}
