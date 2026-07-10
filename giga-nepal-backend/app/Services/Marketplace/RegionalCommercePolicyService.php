<?php

namespace App\Services\Marketplace;

class RegionalCommercePolicyService
{
    public function allowsGlobalFallback(?string $marketplaceCode): bool
    {
        return strtolower((string) $marketplaceCode) === 'global';
    }
}
