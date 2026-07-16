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

class DiscoverBrandLogoJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(public int $brandId, public ?int $requestedBy = null) {}

    public function uniqueId(): string
    {
        return 'brand-logo-discovery:'.$this->brandId;
    }

    public function handle(BrandLogoDiscoveryService $logos): void
    {
        $brand = ProductBrand::find($this->brandId);
        if (! $brand || $brand->logo_verified) {
            return;
        }
        $plan = $logos->discoverOfficialLogo($brand);
        if (($plan['action'] ?? null) === 'stage_for_approval') {
            $logos->stageDiscoveredLogo($brand, $plan, $this->requestedBy);

            return;
        }
        $brand->update(['logo_status' => 'manual_review', 'logo_review_note' => $plan['review_note']]);
    }
}
