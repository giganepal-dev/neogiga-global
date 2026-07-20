<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\B2B\AdminB2BDecisionRequest;
use App\Http\Requests\Admin\B2B\AdminB2BQuotationRequest;
use App\Models\B2B\B2BAccount;
use App\Models\B2B\B2BQuotation;
use App\Models\B2B\B2BQuoteRequest;
use App\Services\B2B\B2BApprovalWorkflowService;
use App\Services\B2B\B2BCommunicationService;
use App\Services\B2B\B2BQuotationService;
use App\Services\B2B\B2BQuotationWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class B2BAdminController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly B2BCommunicationService $communications,
        private readonly B2BApprovalWorkflowService $approvalWorkflow,
    ) {}

    public function accounts(): JsonResponse
    {
        if (! Schema::hasTable('b2b_accounts')) {
            return $this->error('B2B migration is pending.', 503);
        }

        return $this->success(B2BAccount::latest()->paginate(25));
    }

    public function account(int $account): JsonResponse
    {
        if (! Schema::hasTable('b2b_accounts')) {
            return $this->error('B2B migration is pending.', 503);
        }

        return $this->success(B2BAccount::with('users')->findOrFail($account));
    }

    public function approve(Request $request, int $account): JsonResponse
    {
        if (! Schema::hasTable('b2b_accounts')) {
            return $this->error('B2B migration is pending.', 503);
        }

        $record = B2BAccount::findOrFail($account);
        $record->forceFill(['status' => 'approved'])->save();
        $this->approvalWorkflow->markApproved($record, $request->user()?->id);
        $this->communications->accountApproved($record);
        $this->log($record->id, 'b2b_account.approved', $request);

        return $this->success($record->fresh());
    }

    public function reject(AdminB2BDecisionRequest $request, int $account): JsonResponse
    {
        if (! Schema::hasTable('b2b_accounts')) {
            return $this->error('B2B migration is pending.', 503);
        }

        $record = B2BAccount::findOrFail($account);
        $record->forceFill([
            'status' => 'rejected',
            'metadata' => array_merge($record->metadata ?? [], ['rejection_reason' => $request->validated('reason')]),
        ])->save();
        $this->log($record->id, 'b2b_account.rejected', $request);

        return $this->success($record->fresh());
    }

    public function rfqs(): JsonResponse
    {
        if (! Schema::hasTable('b2b_quote_requests')) {
            return $this->error('B2B RFQ migration is pending.', 503);
        }

        return $this->success(B2BQuoteRequest::with('items')->latest()->paginate(25));
    }

    public function rfq(int $rfq): JsonResponse
    {
        if (! Schema::hasTable('b2b_quote_requests')) {
            return $this->error('B2B RFQ migration is pending.', 503);
        }

        return $this->success(B2BQuoteRequest::with('items')->findOrFail($rfq));
    }

    public function createQuotation(AdminB2BQuotationRequest $request, B2BQuotationService $service): JsonResponse
    {
        if (! Schema::hasTable('b2b_quotations')) {
            return $this->error('B2B quotation migration is pending.', 503);
        }

        $quote = $service->create($request->validated(), $request->user()?->id);
        if ($account = B2BAccount::find($quote->b2b_account_id)) {
            app(B2BQuotationWorkflowService::class)->send($quote, $account);
        }

        return $this->success($quote->fresh(['items']), 201);
    }

    public function createQuotationFromRfq(
        AdminB2BQuotationRequest $request,
        int $rfq,
        B2BQuotationWorkflowService $workflow,
    ): JsonResponse {
        if (! Schema::hasTable('b2b_quotations')) {
            return $this->error('B2B quotation migration is pending.', 503);
        }

        $rfqModel = B2BQuoteRequest::with('items')->findOrFail($rfq);
        $accountId = $request->validated('b2b_account_id') ?? $rfqModel->b2b_account_id;
        abort_if(! $accountId, 422, 'B2B account is required to create a quotation.');
        $account = B2BAccount::findOrFail($accountId);

        $quote = $workflow->createFromRfq($rfqModel, $account, $request->validated(), $request->user()?->id);

        return $this->success($quote->load('items'), 201);
    }

    public function quotations(): JsonResponse
    {
        if (! Schema::hasTable('b2b_quotations')) {
            return $this->error('B2B quotation migration is pending.', 503);
        }

        return $this->success(B2BQuotation::with('items')->latest()->paginate(25));
    }

    public function purchaseOrders(): JsonResponse
    {
        if (! Schema::hasTable('b2b_purchase_orders')) {
            return $this->error('B2B purchase order migration is pending.', 503);
        }

        return $this->success(DB::table('b2b_purchase_orders')->latest('id')->paginate(25));
    }

    public function priceLists(): JsonResponse
    {
        if (! Schema::hasTable('b2b_price_lists')) {
            return $this->error('B2B price list migration is pending.', 503);
        }

        return $this->success(DB::table('b2b_price_lists')->latest('id')->paginate(25));
    }

    private function log(?int $accountId, string $action, Request $request): void
    {
        if (! Schema::hasTable('b2b_account_activity_logs')) {
            return;
        }

        DB::table('b2b_account_activity_logs')->insert([
            'b2b_account_id' => $accountId,
            'name' => $action,
            'status' => 'active',
            'metadata' => json_encode(['user_id' => $request->user()?->id, 'ip' => $request->ip()]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
