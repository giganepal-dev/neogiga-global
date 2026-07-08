<?php
namespace App\Http\Controllers\Api\B2B;
use App\Http\Controllers\Controller; use App\Http\Controllers\Concerns\ApiResponses; use App\Models\B2B\B2BQuotation; use App\Services\B2B\B2BContextService; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request; use Illuminate\Support\Facades\Schema;
class B2BQuotationController extends Controller { use ApiResponses;
    public function index(Request $request, B2BContextService $context): JsonResponse { if(!Schema::hasTable('b2b_quotations')) return $this->error('B2B quotation migration is pending.',503); $account=$context->abortUnlessAccount($request->user()); return $this->success(B2BQuotation::where('b2b_account_id',$account->id)->with('items')->latest()->paginate(25)); }
    public function accept(Request $request, int $quotation, B2BContextService $context): JsonResponse { if(!Schema::hasTable('b2b_quotations')) return $this->error('B2B quotation migration is pending.',503); $account=$context->abortUnlessAccount($request->user()); $quote=B2BQuotation::where('b2b_account_id',$account->id)->findOrFail($quotation); abort_if($quote->valid_until->isPast(),422,'Quotation has expired.'); $quote->forceFill(['status'=>'accepted','accepted_at'=>now()])->save(); return $this->success($quote->fresh()); }
}
