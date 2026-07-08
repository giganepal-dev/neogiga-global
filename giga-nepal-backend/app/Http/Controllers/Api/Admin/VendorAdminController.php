<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Vendor\AdminVendorDecisionRequest;
use App\Http\Requests\Admin\Vendor\AdminVendorProductDecisionRequest;
use App\Models\Marketplace\Vendor;
use App\Models\Marketplace\VendorMarketplaceApproval;
use App\Models\Marketplace\VendorPayout;
use App\Models\Marketplace\VendorProduct;
use App\Services\Vendor\VendorApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class VendorAdminController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly VendorApprovalService $approvals)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'max:40'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $hasCommerceStatus = Schema::hasColumn('vendors', 'commerce_status');

        $vendors = Vendor::query()
            ->when($validated['status'] ?? null, function ($query, string $status) use ($hasCommerceStatus) {
                $query->where('status', $status);
                if ($hasCommerceStatus) {
                    $query->orWhere('commerce_status', $status);
                }
            })
            ->with(['country:id,name,iso_code_2', 'profile'])
            ->latest()
            ->paginate($validated['per_page'] ?? 25);

        return $this->success($vendors);
    }

    public function show(Vendor $vendor): JsonResponse
    {
        return $this->success($vendor->load(['profile', 'marketplaceApprovals.marketplace', 'warehouses', 'documents', 'staff']));
    }

    public function approve(AdminVendorDecisionRequest $request, Vendor $vendor): JsonResponse
    {
        return $this->success($this->approvals->approveVendor($vendor, $request->user(), $request));
    }

    public function reject(AdminVendorDecisionRequest $request, Vendor $vendor): JsonResponse
    {
        return $this->success($this->approvals->rejectVendor($vendor, $request->validated('reason') ?? 'Rejected by admin.', $request->user(), $request));
    }

    public function suspend(AdminVendorDecisionRequest $request, Vendor $vendor): JsonResponse
    {
        return $this->success($this->approvals->suspendVendor($vendor, $request->validated('reason') ?? 'Suspended by admin.', $request->user(), $request));
    }

    public function approvals(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'max:40'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $approvals = VendorMarketplaceApproval::query()
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->with(['vendor:id,name,slug,email,status', 'marketplace:id,name,code'])
            ->latest()
            ->paginate($validated['per_page'] ?? 25);

        return $this->success($approvals);
    }

    public function approveMarketplace(Request $request, VendorMarketplaceApproval $approval): JsonResponse
    {
        return $this->success($this->approvals->approveMarketplace($approval, $request->user(), $request));
    }

    public function rejectMarketplace(AdminVendorDecisionRequest $request, VendorMarketplaceApproval $approval): JsonResponse
    {
        return $this->success($this->approvals->rejectMarketplace($approval, $request->validated('reason') ?? 'Rejected by admin.', $request->user(), $request));
    }

    public function pendingProducts(Request $request): JsonResponse
    {
        if (! Schema::hasTable('vendor_products')) {
            return $this->error('Vendor product table is pending migration.', 503);
        }

        return $this->success(VendorProduct::query()->where('status', 'pending_review')->with('vendor:id,name,slug')->latest()->paginate(25));
    }

    public function approveProduct(Request $request, int $product): JsonResponse
    {
        if (! Schema::hasTable('vendor_products')) {
            return $this->error('Vendor product table is pending migration.', 503);
        }

        $product = VendorProduct::findOrFail($product);

        return $this->success($this->approvals->approveProduct($product, $request->user(), $request));
    }

    public function rejectProduct(AdminVendorProductDecisionRequest $request, int $product): JsonResponse
    {
        if (! Schema::hasTable('vendor_products')) {
            return $this->error('Vendor product table is pending migration.', 503);
        }

        $product = VendorProduct::findOrFail($product);

        return $this->success($this->approvals->rejectProduct($product, $request->validated('reason') ?? 'Rejected by admin.', $request->user(), $request));
    }

    public function payouts(): JsonResponse
    {
        if (! Schema::hasTable('vendor_payouts')) {
            return $this->error('Vendor payout table is pending migration.', 503);
        }

        return $this->success(VendorPayout::query()->with('vendor:id,name,slug')->latest()->paginate(25));
    }

    public function markPayoutPaid(Request $request, int $payout): JsonResponse
    {
        if (! Schema::hasTable('vendor_payouts')) {
            return $this->error('Vendor payout table is pending migration.', 503);
        }

        $payout = VendorPayout::findOrFail($payout);

        return $this->success($this->approvals->markPayoutPaid($payout, $request->user(), $request));
    }
}
