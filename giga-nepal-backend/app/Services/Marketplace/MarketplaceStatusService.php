<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceAuditLog;
use InvalidArgumentException;

/**
 * Enable/disable a marketplace with validation + audit (codex §4). Never
 * deletes data. Enabling runs the pre-launch validator and is blocked on any
 * critical failure unless a Super Admin explicitly forces it. Disabling
 * requires a reason and turns off registrations + checkout while preserving
 * orders and admin access.
 */
class MarketplaceStatusService
{
    public function __construct(private readonly MarketplaceLaunchValidator $validator)
    {
    }

    /**
     * @return array{ok:bool, can_activate:bool, checklist:array, marketplace:Marketplace}
     */
    public function enable(Marketplace $marketplace, bool $force = false, bool $isSuperAdmin = false, ?int $userId = null): array
    {
        $result = $this->validator->validate($marketplace);

        if (! $result['can_activate'] && ! ($force && $isSuperAdmin)) {
            // Blocked — do NOT activate. Force is only honoured for Super Admin.
            return [
                'ok' => false,
                'can_activate' => false,
                'checklist' => $result['checklist'],
                'marketplace' => $marketplace,
            ];
        }

        $old = ['is_active' => $marketplace->is_active, 'is_visible' => $marketplace->is_visible];

        $marketplace->is_active = true;
        $marketplace->is_visible = true;
        $marketplace->disabled_at = null;
        $marketplace->disabled_reason = null;
        $marketplace->updated_by = $userId;
        $marketplace->save();

        MarketplaceAuditLog::record(
            $marketplace->id,
            $force && ! $result['can_activate'] ? 'marketplace_force_enabled' : 'marketplace_enabled',
            $old,
            ['is_active' => true, 'is_visible' => true, 'forced' => $force && ! $result['can_activate']],
            $userId,
        );

        return [
            'ok' => true,
            'can_activate' => $result['can_activate'],
            'checklist' => $result['checklist'],
            'marketplace' => $marketplace,
        ];
    }

    /**
     * Disable a marketplace. A non-empty reason is mandatory. Turns off new
     * registrations + checkout; preserves all data and orders.
     */
    public function disable(Marketplace $marketplace, string $reason, ?int $userId = null): Marketplace
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A reason is required to disable a marketplace.');
        }

        $old = [
            'is_active' => $marketplace->is_active,
            'is_visible' => $marketplace->is_visible,
            'checkout_enabled' => $marketplace->checkout_enabled,
        ];

        $marketplace->is_active = false;
        $marketplace->is_visible = false;
        $marketplace->allow_customer_registration = false;
        $marketplace->allow_vendor_registration = false;
        $marketplace->checkout_enabled = false;
        $marketplace->disabled_at = now();
        $marketplace->disabled_reason = $reason;
        $marketplace->updated_by = $userId;
        $marketplace->save();

        MarketplaceAuditLog::record(
            $marketplace->id,
            'marketplace_disabled',
            $old,
            ['is_active' => false, 'reason' => $reason],
            $userId,
        );

        return $marketplace;
    }
}
