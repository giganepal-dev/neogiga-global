<?php

namespace App\Services\B2B;

use App\Models\B2B\B2BAccount;
use App\Models\B2B\B2BQuotation;
use App\Services\Marketing\TransactionalCommunicationService;

class B2BCommunicationService
{
    public function __construct(private readonly TransactionalCommunicationService $communications) {}

    public function applicationReceived(B2BAccount $account): int
    {
        if (! $account->email) {
            return 0;
        }

        return $this->communications->queue('registration_received', $account->email, [
            'customer_name' => $account->name,
            'related_type' => 'b2b_account',
            'related_id' => $account->id,
            'marketplace_id' => $account->marketplace_id,
            'event_id' => 'b2b-apply-'.$account->id,
        ]);
    }

    public function accountApproved(B2BAccount $account): int
    {
        if (! $account->email) {
            return 0;
        }

        return $this->communications->queue('account_approved', $account->email, [
            'customer_name' => $account->name,
            'related_type' => 'b2b_account',
            'related_id' => $account->id,
            'marketplace_id' => $account->marketplace_id,
            'event_id' => 'b2b-approved-'.$account->id,
        ]);
    }

    public function quotationReady(B2BQuotation $quotation, B2BAccount $account): int
    {
        if (! $account->email) {
            return 0;
        }

        return $this->communications->queue('quotation_ready', $account->email, [
            'customer_name' => $account->name,
            'quotation_number' => $quotation->quotation_number,
            'related_type' => 'b2b_quotation',
            'related_id' => $quotation->id,
            'marketplace_id' => $account->marketplace_id,
            'event_id' => 'b2b-quote-ready-'.$quotation->id,
        ]);
    }

    public function quotationAccepted(B2BQuotation $quotation, B2BAccount $account): int
    {
        if (! $account->email) {
            return 0;
        }

        return $this->communications->queue('quotation_accepted', $account->email, [
            'customer_name' => $account->name,
            'quotation_number' => $quotation->quotation_number,
            'related_type' => 'b2b_quotation',
            'related_id' => $quotation->id,
            'marketplace_id' => $account->marketplace_id,
            'event_id' => 'b2b-quote-accepted-'.$quotation->id,
        ]);
    }
}
