<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\SellerStoreProductRequest;
use App\Http\Requests\Seller\SellerProductAttributesRequest;
use App\Http\Requests\Seller\SellerProductDocumentRequest;
use App\Http\Requests\Seller\SellerProductSpecRequest;
use App\Http\Requests\Seller\SellerProductVariantRequest;
use App\Http\Requests\Seller\SellerProductWarrantyRequest;
use App\Http\Requests\Seller\SellerUpdateProductRequest;
use App\Models\Marketplace\VendorProduct;
use App\Services\Seller\SellerContextService;
use App\Services\Seller\SellerProductDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function storeVariant(SellerProductVariantRequest $request, int $product, SellerProductDetailService $details): JsonResponse
    {
        $vendorProduct = $this->ownedProduct($request, $product);
        $catalogProductId = $details->productId($vendorProduct);
        abort_if(! Schema::hasTable('product_variants'), 503, 'Product variants table is not available.');

        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $catalogProductId,
            ...$request->validated(),
            'options' => json_encode($request->validated('options') ?? []),
            'price' => $request->validated('price') ?? 0,
            'sale_price' => $request->validated('sale_price'),
            'stock_quantity' => $request->validated('stock_quantity') ?? 0,
            'is_active' => $request->validated('is_active') ?? true,
            'sort_order' => $request->validated('sort_order') ?? 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $details->log($vendorProduct, $request->user()->id, 'seller.product.variant.created', ['variant_id' => $variantId]);

        return $this->success(DB::table('product_variants')->find($variantId), 201);
    }

    public function storeSpec(SellerProductSpecRequest $request, int $product, SellerProductDetailService $details): JsonResponse
    {
        $vendorProduct = $this->ownedProduct($request, $product);
        $catalogProductId = $details->productId($vendorProduct);
        abort_if(! Schema::hasTable('product_specs'), 503, 'Product specs table is not available.');

        $specId = DB::table('product_specs')->insertGetId([
            'product_id' => $catalogProductId,
            ...$request->validated(),
            'sort_order' => $request->validated('sort_order') ?? 100,
            'is_visible' => $request->validated('is_visible') ?? true,
            'is_filterable' => $request->validated('is_filterable') ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $details->log($vendorProduct, $request->user()->id, 'seller.product.spec.created', ['spec_id' => $specId]);

        return $this->success(DB::table('product_specs')->find($specId), 201);
    }

    public function storeDocument(SellerProductDocumentRequest $request, int $product, SellerProductDetailService $details): JsonResponse
    {
        $vendorProduct = $this->ownedProduct($request, $product);
        $catalogProductId = $details->productId($vendorProduct);
        $type = $request->validated('document_type') ?? 'datasheet';
        $table = ['datasheet' => 'product_datasheets', 'certificate' => 'product_certificates', 'manual' => 'product_manuals'][$type];
        abort_if(! Schema::hasTable($table), 503, 'Product document table is not available.');

        $data = $request->validated();
        $documentId = DB::table($table)->insertGetId([
            'product_id' => $catalogProductId,
            'title' => $data['title'],
            'document_type' => $type,
            'source_url' => $data['source_url'] ?? null,
            'file_path' => $data['file_path'] ?? null,
            'mime_type' => $data['mime_type'] ?? null,
            'file_size' => $data['file_size'] ?? null,
            'status' => 'pending_review',
            'uploaded_by' => $request->user()->id,
            'metadata' => json_encode($data['metadata'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $details->log($vendorProduct, $request->user()->id, 'seller.product.document.created', ['table' => $table, 'document_id' => $documentId]);

        return $this->success(DB::table($table)->find($documentId), 201);
    }

    public function storeWarranty(SellerProductWarrantyRequest $request, int $product, SellerProductDetailService $details): JsonResponse
    {
        $vendorProduct = $this->ownedProduct($request, $product);
        $catalogProductId = $details->productId($vendorProduct);
        abort_if(! Schema::hasTable('product_warranties'), 503, 'Product warranties table is not available.');

        $data = $request->validated();
        $warrantyId = DB::table('product_warranties')->insertGetId([
            'product_id' => $catalogProductId,
            'warranty_type' => $data['warranty_type'] ?? null,
            'warranty_period' => $data['warranty_period'] ?? null,
            'terms' => $data['terms'] ?? null,
            'claim_requirements' => $data['claim_requirements'] ?? null,
            'is_active' => true,
            'updated_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('products')->where('id', $catalogProductId)->update(array_filter([
            'warranty_type' => $data['warranty_type'] ?? null,
            'warranty_period' => $data['warranty_period'] ?? null,
            'warranty_terms' => $data['terms'] ?? null,
            'return_policy' => $data['return_policy'] ?? null,
            'country_of_origin' => $data['country_of_origin'] ?? null,
            'updated_at' => now(),
        ], fn ($value) => $value !== null));
        $details->log($vendorProduct, $request->user()->id, 'seller.product.warranty.created', ['warranty_id' => $warrantyId]);

        return $this->success(DB::table('product_warranties')->find($warrantyId), 201);
    }

    public function storeAttributes(SellerProductAttributesRequest $request, int $product, SellerProductDetailService $details): JsonResponse
    {
        $vendorProduct = $this->ownedProduct($request, $product);
        $catalogProductId = $details->productId($vendorProduct);
        $data = $request->validated();

        DB::table('products')->where('id', $catalogProductId)->update([
            'attributes' => json_encode($data['attributes']),
            'package_includes' => isset($data['package_includes']) ? json_encode($data['package_includes']) : null,
            'use_cases' => isset($data['use_cases']) ? json_encode($data['use_cases']) : null,
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
            'search_keywords' => $data['search_keywords'] ?? null,
            'safety_certification' => $data['safety_certification'] ?? null,
            'compliance_certification' => $data['compliance_certification'] ?? null,
            'updated_at' => now(),
        ]);
        $details->log($vendorProduct, $request->user()->id, 'seller.product.attributes.updated', ['product_id' => $catalogProductId]);

        return $this->success(DB::table('products')->find($catalogProductId));
    }

    private function ownedProduct(Request $request, int $product): VendorProduct
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        abort_if(! Schema::hasTable('vendor_products'), 503, 'Seller product table is pending migration.');
        $vendorProduct = VendorProduct::findOrFail($product);
        abort_if($vendorProduct->vendor_id !== $vendor->id, 403, 'This product belongs to another vendor.');
        abort_if($vendorProduct->status === 'approved', 422, 'Approved products must be changed through a new review request.');

        return $vendorProduct;
    }
}
