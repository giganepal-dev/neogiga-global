<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\SellerMarketplaceApplicationRequest;
use App\Http\Requests\Seller\SellerUpdateProfileRequest;
use App\Models\Marketplace\VendorMarketplaceApproval;
use App\Services\Seller\SellerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerProfileController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly SellerContextService $context)
    {
    }

    public function profile(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        return $this->success($vendor->load(['profile', 'marketplaceApprovals.marketplace', 'warehouses']));
    }

    public function update(SellerUpdateProfileRequest $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        $data = $request->validated();
        $profileData = array_intersect_key($data, array_flip(['about', 'business_type', 'return_policy', 'warranty_policy']));
        $vendorData = array_diff_key($data, $profileData);

        if ($vendorData !== []) {
            $vendor->fill($vendorData)->save();
        }

        if ($profileData !== []) {
            $vendor->profile()->updateOrCreate(['vendor_id' => $vendor->id], $profileData);
        }

        return $this->success($vendor->fresh()->load('profile'));
    }

    public function marketplaceApprovals(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        return $this->success($vendor->marketplaceApprovals()->with('marketplace:id,name,code')->get());
    }

    public function applyMarketplace(SellerMarketplaceApplicationRequest $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        $data = $request->validated();

        $approval = VendorMarketplaceApproval::updateOrCreate(
            ['vendor_id' => $vendor->id, 'marketplace_id' => $data['marketplace_id']],
            ['status' => 'pending', 'application_notes' => $data['application_notes'] ?? null],
        );

        return $this->success($approval, $approval->wasRecentlyCreated ? 201 : 200);
    }
}
