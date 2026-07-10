<?php

namespace App\Services\Pricing;

use App\Models\Marketplace\Marketplace;
use Carbon\CarbonInterface;

/**
 * Immutable inputs for one price resolution. costBasisAmount is expressed in
 * the marketplace currency (the caller is responsible for landing/converting
 * the cost per the rule's cost_basis before handing it here — see
 * CENTRAL_PRICING_ENGINE_GUIDE.md for the USD→local pipeline).
 */
class PricingContext
{
    public function __construct(
        public readonly int $productId,
        public readonly Marketplace $marketplace,
        public readonly float $costBasisAmount,
        public readonly string $currencyCode,
        public readonly int $quantity = 1,
        public readonly ?string $customerSegment = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $brandId = null,
        public readonly ?int $manufacturerId = null,
        public readonly ?int $sellerId = null,
        public readonly ?int $warehouseId = null,
        public readonly ?int $countryId = null,
        public readonly ?CarbonInterface $at = null,
    ) {
    }

    public function time(): CarbonInterface
    {
        return $this->at ?? now();
    }
}
