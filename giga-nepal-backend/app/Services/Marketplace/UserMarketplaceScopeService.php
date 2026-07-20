<?php

namespace App\Services\Marketplace;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserMarketplaceScopeService
{
    public function __construct(
        private readonly GlobalMarketplaceContextService $marketplaceContext,
    ) {}

    public function homeMarketplaceIdForRegistration(Request $request): ?int
    {
        $context = $this->marketplaceContext->context($request);

        return $context['current']?->id;
    }

    /**
     * Ensures a logged-in customer checks out only on their home regional store.
     */
    public function assertCanPurchase(?User $user, ?int $cartMarketplaceId): void
    {
        if (! $user || ! $user->home_marketplace_id || ! $cartMarketplaceId) {
            return;
        }

        if ((int) $user->home_marketplace_id !== (int) $cartMarketplaceId) {
            throw ValidationException::withMessages([
                'marketplace' => 'Your account is registered for a different regional store. Please complete checkout on your home marketplace.',
            ]);
        }
    }
}
