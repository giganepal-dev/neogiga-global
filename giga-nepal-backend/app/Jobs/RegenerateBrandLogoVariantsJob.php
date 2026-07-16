<?php

namespace App\Jobs;

use App\Models\Marketplace\ProductBrand;
use App\Services\Catalog\BrandLogoDiscoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegenerateBrandLogoVariantsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(public int $brandId) {}

    public function uniqueId(): string
    {
        return 'brand-logo-variants:'.$this->brandId;
    }

    public function handle(BrandLogoDiscoveryService $logos): void
    {
        $brand = ProductBrand::find($this->brandId);
        if ($brand?->logo_verified) {
            $logos->regenerateVariants($brand);
        }
    }
}
