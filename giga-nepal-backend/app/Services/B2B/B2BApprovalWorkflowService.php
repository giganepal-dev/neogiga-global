<?php

namespace App\Services\B2B;

use App\Models\B2B\B2BAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class B2BApprovalWorkflowService
{
    public function start(B2BAccount $account): void
    {
        if (! Schema::hasTable('b2b_approval_workflows')) {
            return;
        }

        DB::table('b2b_approval_workflows')->insert([
            'b2b_account_id' => $account->id,
            'name' => 'institutional_onboarding',
            'status' => 'pending',
            'metadata' => json_encode([
                'steps' => [
                    ['key' => 'documents_submitted', 'status' => 'completed', 'at' => now()->toIso8601String()],
                    ['key' => 'admin_review', 'status' => 'pending'],
                    ['key' => 'account_activation', 'status' => 'pending'],
                ],
                'account_type' => $account->type,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markApproved(B2BAccount $account, ?int $reviewerId = null): void
    {
        if (! Schema::hasTable('b2b_approval_workflows')) {
            return;
        }

        $workflow = DB::table('b2b_approval_workflows')
            ->where('b2b_account_id', $account->id)
            ->where('name', 'institutional_onboarding')
            ->latest('id')
            ->first();

        if (! $workflow) {
            return;
        }

        $metadata = json_decode($workflow->metadata ?? '{}', true) ?: [];
        $metadata['steps'] = collect($metadata['steps'] ?? [])->map(function ($step) use ($reviewerId) {
            if (($step['key'] ?? '') === 'admin_review') {
                $step['status'] = 'completed';
                $step['reviewer_id'] = $reviewerId;
                $step['at'] = now()->toIso8601String();
            }
            if (($step['key'] ?? '') === 'account_activation') {
                $step['status'] = 'completed';
                $step['at'] = now()->toIso8601String();
            }

            return $step;
        })->all();

        DB::table('b2b_approval_workflows')->where('id', $workflow->id)->update([
            'status' => 'approved',
            'metadata' => json_encode($metadata),
            'updated_at' => now(),
        ]);
    }
}
