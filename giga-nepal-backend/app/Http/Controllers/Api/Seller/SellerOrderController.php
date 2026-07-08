<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\SellerOrderStatusRequest;
use App\Models\Marketplace\VendorOrder;
use App\Services\Seller\SellerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SellerOrderController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly SellerContextService $context)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        if (! Schema::hasTable('vendor_orders')) {
            return $this->error('Vendor order table is pending migration.', 503);
        }

        return $this->success(VendorOrder::query()->where('vendor_id', $vendor->id)->latest()->paginate(25));
    }

    public function show(Request $request, int $order): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        if (! Schema::hasTable('vendor_orders')) {
            return $this->error('Vendor order table is pending migration.', 503);
        }
        $order = VendorOrder::findOrFail($order);
        abort_if($order->vendor_id !== $vendor->id, 403, 'This order belongs to another vendor.');

        return $this->success($order->load('items'));
    }

    public function updateStatus(SellerOrderStatusRequest $request, int $order): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        if (! Schema::hasTable('vendor_orders')) {
            return $this->error('Vendor order table is pending migration.', 503);
        }
        $order = VendorOrder::findOrFail($order);
        abort_if($order->vendor_id !== $vendor->id, 403, 'This order belongs to another vendor.');

        $order->forceFill([
            'status' => $request->validated('status'),
            'metadata' => array_merge($order->metadata ?? [], ['seller_status_notes' => $request->validated('notes')]),
        ])->save();

        return $this->success($order->fresh());
    }
}
