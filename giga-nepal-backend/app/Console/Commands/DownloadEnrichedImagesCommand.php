<?php

namespace App\Console\Commands;

use App\Models\Marketplace\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DownloadEnrichedImagesCommand extends Command
{
    protected $signature = 'neogiga:download-enriched-images
                            {--timeout=15 : Download timeout per image}
                            {--concurrency=5 : Concurrent downloads}
                            {--retry=2 : Retry count per image}';

    protected $description = 'Download product images locally for enriched products, replacing external URLs.';

    public function handle(): int
    {
        $images = ProductImage::where('source_name', 'neogiga_mpn_enrichment')
            ->where(function ($q) {
                $q->where('file_path', '')->orWhereNull('file_path');
            })
            ->get();

        if ($images->isEmpty()) {
            $this->info('All images already downloaded locally.');

            return self::SUCCESS;
        }

        $this->info("Downloading {$images->count()} images...");
        $bar = $this->output->createProgressBar($images->count());

        $downloaded = 0;
        $failed = 0;
        $timeout = (int) $this->option('timeout');
        $retries = (int) $this->option('retry');

        foreach ($images as $image) {
            $url = $image->original_url ?: $image->source_url;
            if (empty($url)) {
                $failed++;
                $bar->advance();
                continue;
            }

            $success = false;
            for ($attempt = 0; $attempt <= $retries; $attempt++) {
                try {
                    $response = Http::withUserAgent('Mozilla/5.0 (compatible; NeoGigaCatalog/1.0)')
                        ->timeout($timeout)
                        ->connectTimeout(5)
                        ->get($url);

                    if ($response->successful()) {
                        $body = $response->body();
                        $contentType = $response->header('Content-Type');
                        $ext = match (true) {
                            str_contains((string) $contentType, 'png') => 'png',
                            str_contains((string) $contentType, 'webp') => 'webp',
                            str_contains((string) $contentType, 'gif') => 'gif',
                            str_contains((string) $contentType, 'svg') => 'svg',
                            default => 'jpg',
                        };

                        $filename = 'products/enriched/' . $image->product_id . '_' . ($image->id ?? uniqid()) . '.' . $ext;
                        Storage::disk('public')->put($filename, $body);

                        $image->update([
                            'file_path' => $filename,
                            'file_name' => basename($filename),
                            'mime_type' => $contentType ?: 'image/jpeg',
                            'file_size' => strlen($body),
                            'storage_disk' => 'public',
                            'downloaded_at' => now(),
                        ]);

                        $downloaded++;
                        $success = true;
                        break;
                    }
                } catch (\Exception $e) {
                    if ($attempt === $retries) {
                        $this->warn("  Failed: {$image->product_id} — {$url} — {$e->getMessage()}");
                    }
                }

                if ($attempt < $retries) {
                    usleep(500000); // 500ms backoff
                }
            }

            if (! $success) {
                $failed++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Downloaded: {$downloaded} | Failed: {$failed}");

        return self::SUCCESS;
    }
}
