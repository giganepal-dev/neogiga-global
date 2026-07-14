<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\AdminGenericGroupRequest;
use App\Http\Requests\Admin\Product\AdminGenericSuggestionRequest;
use App\Http\Requests\Admin\Product\AdminProductDecisionRequest;
use App\Models\Marketplace\VendorProduct;
use App\Models\Marketplace\ProductImage;
use App\Services\Product\ProductApprovalService;
use App\Services\Product\ProductImageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductAdminController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('products')) {
            return $this->error('Products table is not available.', 503);
        }

        $query = DB::table('products')->latest('id');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('approval_status') && Schema::hasColumn('products', 'approval_status')) {
            $query->where('approval_status', $request->string('approval_status'));
        }

        return $this->success($query->paginate(25));
    }

    public function show(int $product): JsonResponse
    {
        if (! Schema::hasTable('products')) {
            return $this->error('Products table is not available.', 503);
        }

        $record = DB::table('products')->where('id', $product)->first();
        abort_if(! $record, 404);

        return $this->success([
            'product' => $record,
            'variants' => Schema::hasTable('product_variants') ? DB::table('product_variants')->where('product_id', $product)->get() : [],
            'specs' => Schema::hasTable('product_specs') ? DB::table('product_specs')->where('product_id', $product)->get() : [],
            'warranties' => Schema::hasTable('product_warranties') ? DB::table('product_warranties')->where('product_id', $product)->get() : [],
            'datasheets' => Schema::hasTable('product_datasheets') ? DB::table('product_datasheets')->where('product_id', $product)->get() : [],
            'images' => Schema::hasTable('product_images')
                ? ProductImage::where('product_id', $product)->orderByDesc('is_primary')->orderBy('sort_order')->get()
                    ->map(fn (ProductImage $image) => app(ProductImageManager::class)->serialize($image))
                : [],
        ]);
    }

    public function pending(): JsonResponse
    {
        if (! Schema::hasTable('vendor_products')) {
            return $this->error('Vendor products table is not available.', 503);
        }

        return $this->success(VendorProduct::query()->where('status', 'pending_review')->latest()->paginate(25));
    }

    public function approve(AdminProductDecisionRequest $request, int $product, ProductApprovalService $approval): JsonResponse
    {
        $vendorProduct = VendorProduct::findOrFail($product);

        return $this->success($approval->approveVendorProduct($vendorProduct, $request));
    }

    public function reject(AdminProductDecisionRequest $request, int $product, ProductApprovalService $approval): JsonResponse
    {
        $vendorProduct = VendorProduct::findOrFail($product);

        return $this->success($approval->rejectVendorProduct($vendorProduct, $request, $request->validated('reason')));
    }

    public function genericGroups(): JsonResponse
    {
        if (! Schema::hasTable('product_generic_groups')) {
            return $this->error('Product generic groups table is not available.', 503);
        }

        return $this->success(DB::table('product_generic_groups')->orderBy('name')->paginate(50));
    }

    public function storeGenericGroup(AdminGenericGroupRequest $request): JsonResponse
    {
        if (! Schema::hasTable('product_generic_groups')) {
            return $this->error('Product generic groups table is not available.', 503);
        }

        $data = $request->validated();
        $slug = $data['slug'] ?? Str::slug($data['name']);
        $id = DB::table('product_generic_groups')->insertGetId([
            'name' => $data['name'],
            'slug' => $this->uniqueGenericGroupSlug($slug),
            'category_id' => $data['category_id'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(DB::table('product_generic_groups')->find($id), 201);
    }

    public function storeGenericSuggestion(AdminGenericSuggestionRequest $request, int $product): JsonResponse
    {
        if (! Schema::hasTable('product_generic_suggestions')) {
            return $this->error('Product generic suggestions table is not available.', 503);
        }

        $data = $request->validated();
        $id = DB::table('product_generic_suggestions')->insertGetId([
            'source_product_id' => $product,
            'suggested_product_id' => $data['suggested_product_id'] ?? null,
            'suggested_name' => $data['suggested_name'] ?? null,
            'suggestion_type' => $data['suggestion_type'],
            'priority' => $data['priority'] ?? 100,
            'reason' => $data['reason'] ?? null,
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(DB::table('product_generic_suggestions')->find($id), 201);
    }

    public function updateGenericSuggestion(AdminGenericSuggestionRequest $request, int $suggestion): JsonResponse
    {
        if (! Schema::hasTable('product_generic_suggestions')) {
            return $this->error('Product generic suggestions table is not available.', 503);
        }

        $data = $request->validated();
        DB::table('product_generic_suggestions')->where('id', $suggestion)->update([
            'suggested_product_id' => $data['suggested_product_id'] ?? null,
            'suggested_name' => $data['suggested_name'] ?? null,
            'suggestion_type' => $data['suggestion_type'],
            'priority' => $data['priority'] ?? 100,
            'reason' => $data['reason'] ?? null,
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'updated_at' => now(),
        ]);

        return $this->success(DB::table('product_generic_suggestions')->find($suggestion));
    }

    public function deleteGenericSuggestion(int $suggestion): JsonResponse
    {
        if (! Schema::hasTable('product_generic_suggestions')) {
            return $this->error('Product generic suggestions table is not available.', 503);
        }

        DB::table('product_generic_suggestions')->where('id', $suggestion)->update(['is_active' => false, 'updated_at' => now()]);

        return $this->success(['deleted' => true]);
    }

    private function uniqueGenericGroupSlug(string $slug): string
    {
        $base = $slug ?: 'generic-group';
        $candidate = $base;
        $i = 1;
        while (DB::table('product_generic_groups')->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . ++$i;
        }

        return $candidate;
    }
}
