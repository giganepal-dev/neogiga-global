<?php

namespace App\Services\Marketing;

use App\Models\User;
use Illuminate\Support\Facades\URL;

class AccountCommunicationService
{
    public function __construct(private TransactionalCommunicationService $communications) {}

    /** @return array{registration_received:int,email_verification:int} */
    public function registration(User $user, ?int $marketplaceId = null): array
    {
        $context = ['customer_name' => $user->name, 'related_type' => 'user', 'related_id' => $user->id, 'marketplace_id' => $marketplaceId];

        return [
            'registration_received' => $this->communications->queue('registration_received', $user->email, $context + ['event_id' => 'registration-'.$user->id]),
            'email_verification' => $this->verification($user, $marketplaceId),
        ];
    }

    public function verification(User $user, ?int $marketplaceId = null): int
    {
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1(mb_strtolower($user->email)),
        ]);

        return $this->communications->queue('email_verification', $user->email, [
            'customer_name' => $user->name,
            'verification_url' => $url,
            'related_type' => 'user',
            'related_id' => $user->id,
            'marketplace_id' => $marketplaceId,
            'event_id' => 'verification-'.now()->timestamp,
        ]);
    }

    public function verified(User $user, ?int $marketplaceId = null): array
    {
        $context = [
            'customer_name' => $user->name,
            'related_type' => 'user',
            'related_id' => $user->id,
            'marketplace_id' => $marketplaceId,
            'event_id' => 'verified-'.optional($user->email_verified_at)->timestamp,
        ];

        return [
            'account_activation' => $this->communications->queue('account_activation', $user->email, $context),
            'welcome' => $this->communications->queue('welcome', $user->email, $context),
        ];
    }

    public function passwordReset(User $user, string $token, string $url, ?int $marketplaceId = null): int
    {
        return $this->communications->queue('password_reset', $user->email, [
            'customer_name' => $user->name,
            'password_reset_url' => $url,
            'related_type' => 'user',
            'related_id' => $user->id,
            'marketplace_id' => $marketplaceId,
            'event_id' => 'password-reset-'.hash('sha256', $token),
        ]);
    }

    public function passwordChanged(User $user, ?int $marketplaceId = null): int
    {
        return $this->communications->queue('password_changed', $user->email, [
            'customer_name' => $user->name,
            'related_type' => 'user',
            'related_id' => $user->id,
            'marketplace_id' => $marketplaceId,
            'event_id' => 'password-changed-'.now()->timestamp,
        ]);
    }

    public function application(User $user, string $type, int $applicationId, ?int $marketplaceId = null): int
    {
        $event = match ($type) {
            'seller' => 'seller_application_received',
            'distributor' => 'distributor_application_received',
            default => 'registration_received',
        };

        return $this->communications->queue($event, $user->email, [
            'customer_name' => $user->name,
            'related_type' => $type.'_application',
            'related_id' => $applicationId,
            'marketplace_id' => $marketplaceId,
            'event_id' => $type.'-application-'.$applicationId,
        ]);
    }
}
