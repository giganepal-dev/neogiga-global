<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\VendorPayout;
use App\Services\Seller\SellerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SellerPayoutController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly SellerContextService $context)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        if (! Schema::hasTable('vendor_payouts')) {
            return $this->error('Vendor payout table is pending migration.', 503);
        }

        return $this->success(VendorPayout::query()->where('vendor_id', $vendor->id)->latest()->paginate(25));
    }
}
