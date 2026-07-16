<?php

namespace App\Jobs;

use App\Models\Marketplace\BrandLogoHistory;
use App\Models\Marketplace\ProductBrand;
use App\Services\Catalog\BrandLogoDiscoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyBrandLogoJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(public int $brandId, public int $historyId) {}

    public function uniqueId(): string
    {
        return 'brand-logo-verify:'.$this->brandId.':'.$this->historyId;
    }

    public function handle(BrandLogoDiscoveryService $logos): void
    {
        $brand = ProductBrand::find($this->brandId);
        $history = BrandLogoHistory::find($this->historyId);
        if (! $brand || ! $history || $history->brand_id !== $brand->id || $history->status !== 'pending') {
            return;
        }
        $plan = $logos->discoverOfficialLogo($brand);
        $history->update(['review_note' => $plan['review_note'], 'confidence' => $plan['confidence']]);
    }
}
