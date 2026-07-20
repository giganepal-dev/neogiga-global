<?php

namespace App\Http\Controllers\Api\B2B;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\B2B\B2BQuotation;
use App\Services\B2B\B2BContextService;
use App\Services\B2B\B2BQuotationWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class B2BQuotationController extends Controller
{
    use ApiResponses;

    public function index(Request $request, B2BContextService $context): JsonResponse
    {
        if (! Schema::hasTable('b2b_quotations')) {
            return $this->error('B2B quotation migration is pending.', 503);
        }

        $account = $context->abortUnlessAccount($request->user());

        return $this->success(
            B2BQuotation::where('b2b_account_id', $account->id)->with('items')->latest()->paginate(25)
        );
    }

    public function accept(
        Request $request,
        int $quotation,
        B2BContextService $context,
        B2BQuotationWorkflowService $workflow,
    ): JsonResponse {
        if (! Schema::hasTable('b2b_quotations')) {
            return $this->error('B2B quotation migration is pending.', 503);
        }

        $account = $context->abortUnlessAccount($request->user());
        $quote = B2BQuotation::where('b2b_account_id', $account->id)->findOrFail($quotation);

        return $this->success($workflow->accept($quote, $account));
    }

    public function pay(
        Request $request,
        int $quotation,
        B2BContextService $context,
        B2BQuotationWorkflowService $workflow,
    ): JsonResponse {
        if (! Schema::hasTable('b2b_quotations')) {
            return $this->error('B2B quotation migration is pending.', 503);
        }

        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:80'],
        ]);

        $account = $context->abortUnlessAccount($request->user());
        $quote = B2BQuotation::where('b2b_account_id', $account->id)->findOrFail($quotation);
        $order = $workflow->pay($quote, $account, $request->user(), $data['payment_method']);

        return $this->success([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
        ], 201);
    }
}
