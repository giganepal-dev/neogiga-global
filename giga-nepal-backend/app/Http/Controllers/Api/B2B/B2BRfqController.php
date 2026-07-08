<?php
namespace App\Http\Controllers\Api\B2B;
use App\Http\Controllers\Controller; use App\Http\Controllers\Concerns\ApiResponses; use App\Http\Requests\B2B\B2BRfqRequest; use App\Models\B2B\B2BQuoteRequest; use App\Services\B2B\B2BContextService; use App\Services\B2B\B2BQuoteService; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request; use Illuminate\Support\Facades\Schema;
class B2BRfqController extends Controller { use ApiResponses;
    public function index(Request $request, B2BContextService $context): JsonResponse { if(!Schema::hasTable('b2b_quote_requests')) return $this->error('B2B RFQ migration is pending.',503); $account=$context->abortUnlessAccount($request->user()); return $this->success(B2BQuoteRequest::where('b2b_account_id',$account->id)->with('items')->latest()->paginate(25)); }
    public function store(B2BRfqRequest $request, B2BContextService $context, B2BQuoteService $service): JsonResponse { if(!Schema::hasTable('b2b_quote_requests')) return $this->error('B2B RFQ migration is pending.',503); $account=$context->accountFor($request->user()); return $this->success($service->create($request->validated(),$account?->id),201); }
}
