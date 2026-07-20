<?php

namespace App\Services\Reseller;

use App\Models\Reseller;
use App\Models\ResellerTerritory;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ResellerContextService
{
    public function resellerFor(User $user): ?Reseller
    {
        return Reseller::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
    }

    public function abortUnlessReseller(User $user): Reseller
    {
        $reseller = $this->resellerFor($user);
        if (! $reseller) {
            throw new AccessDeniedHttpException('No reseller account linked.');
        }

        return $reseller;
    }

    public function assertMarketplaceAccess(Reseller $reseller, ?int $marketplaceId): void
    {
        if (! $marketplaceId) {
            return;
        }

        $allowed = ResellerTerritory::query()
            ->where('reseller_id', $reseller->id)
            ->where('marketplace_id', $marketplaceId)
            ->where('is_active', true)
            ->exists();

        if (! $allowed && (int) $reseller->home_marketplace_id !== (int) $marketplaceId) {
            throw new AccessDeniedHttpException('This marketplace is outside your approved territory.');
        }
    }
}
