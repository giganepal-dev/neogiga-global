<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\SellerStoreProductRequest;
use App\Http\Requests\Seller\SellerUpdateProductRequest;
use App\Models\Marketplace\VendorProduct;
use App\Services\Seller\SellerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SellerProductController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly SellerContextService $context)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        if (! Schema::hasTable('vendor_products')) {
            return $this->error('Seller product table is pending migration.', 503);
        }

        return $this->success(VendorProduct::query()->where('vendor_id', $vendor->id)->latest()->paginate(25));
    }

    public function store(SellerStoreProductRequest $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        if (! Schema::hasTable('vendor_products')) {
            return $this->error('Seller product table is pending migration.', 503);
        }

        $product = VendorProduct::create([
            ...$request->validated(),
            'vendor_id' => $vendor->id,
            'status' => 'draft',
        ]);

        return $this->success($product, 201);
    }

    public function show(Request $request, int $product): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        if (! Schema::hasTable('vendor_products')) {
            return $this->error('Seller product table is pending migration.', 503);
        }
        $product = VendorProduct::findOrFail($product);
        abort_if($product->vendor_id !== $vendor->id, 403, 'This product belongs to another vendor.');

        return $this->success($product);
    }

    public function update(SellerUpdateProductRequest $request, int $product): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        if (! Schema::hasTable('vendor_products')) {
            return $this->error('Seller product table is pending migration.', 503);
        }
        $product = VendorProduct::findOrFail($product);
        abort_if($product->vendor_id !== $vendor->id, 403, 'This product belongs to another vendor.');
        abort_if($product->status === 'approved', 422, 'Approved products must be changed through a new review request.');

        $product->fill($request->validated())->save();

        return $this->success($product->fresh());
    }

    public function submitReview(Request $request, int $product): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        if (! Schema::hasTable('vendor_products')) {
            return $this->error('Seller product table is pending migration.', 503);
        }
        $product = VendorProduct::findOrFail($product);
        abort_if($product->vendor_id !== $vendor->id, 403, 'This product belongs to another vendor.');

        $product->forceFill([
            'status' => 'pending_review',
            'submitted_by' => $request->user()->id,
            'submitted_at' => now(),
        ])->save();

        return $this->success($product->fresh());
    }
}
