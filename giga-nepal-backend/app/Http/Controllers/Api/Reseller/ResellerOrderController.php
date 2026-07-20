<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Reseller\ResellerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResellerOrderController extends Controller
{
    use ApiResponses;

    public function index(Request $request, ResellerContextService $context): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());

        return $this->success(
            Order::query()->where('reseller_id', $reseller->id)->latest()->paginate(25)
        );
    }
}
