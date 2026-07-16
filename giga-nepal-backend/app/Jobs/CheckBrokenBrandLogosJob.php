<?php

namespace App\Jobs;

use App\Models\Marketplace\ProductBrand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CheckBrokenBrandLogosJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function uniqueId(): string
    {
        return 'brand-logo-broken-check';
    }

    public function handle(): void
    {
        $disk = (string) config('brand_logos.disk', 'public');
        ProductBrand::query()->where('logo_verified', true)->whereNotNull('logo_path')->eachById(function (ProductBrand $brand) use ($disk): void {
            if (! str_starts_with((string) $brand->logo_path, 'http') && ! Storage::disk($disk)->exists($brand->logo_path)) {
                $brand->update(['logo_status' => 'manual_review', 'logo_verified' => false, 'logo_review_note' => 'Verified logo file is missing and requires replacement.']);
            }
        });
    }
}
