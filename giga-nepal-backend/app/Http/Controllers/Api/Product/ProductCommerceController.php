<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Services\Inventory\RegionStockService;
use App\Services\Product\GenericProductSuggestionService;
use App\Services\Product\ProductVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductCommerceController extends Controller
{
    use ApiResponses;

    public function attributes(string|int $product, ProductVisibilityService $visibility): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);
        $attributes = $record->attributes ?? [];
        if (is_string($attributes)) {
            $attributes = json_decode($attributes, true) ?: [];
        }

        return $this->success($attributes);
    }

    public function specs(string|int $product, ProductVisibilityService $visibility): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);

        return $this->success(Schema::hasTable('product_specs') ? DB::table('product_specs')->where('product_id', $record->id)->orderBy('sort_order')->get() : []);
    }

    public function variants(string|int $product, ProductVisibilityService $visibility): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);

        return $this->success(Schema::hasTable('product_variants') ? DB::table('product_variants')->where('product_id', $record->id)->where('is_active', true)->orderBy('sort_order')->get() : []);
    }

    public function datasheets(string|int $product, ProductVisibilityService $visibility): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);

        return $this->success($this->documents($record->id, 'product_datasheets'));
    }

    public function warranty(string|int $product, ProductVisibilityService $visibility): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);
        $warranties = Schema::hasTable('product_warranties')
            ? DB::table('product_warranties')->where('product_id', $record->id)->where('is_active', true)->latest('id')->get()
            : collect();

        return $this->success([
            'product' => [
                'warranty_type' => $record->warranty_type ?? null,
                'warranty_period' => $record->warranty_period ?? null,
                'warranty_terms' => $record->warranty_terms ?? null,
                'return_policy' => $record->return_policy ?? null,
                'country_of_origin' => $record->country_of_origin ?? null,
            ],
            'warranties' => $warranties,
        ]);
    }

    public function genericSuggestions(string|int $product, ProductVisibilityService $visibility, GenericProductSuggestionService $suggestions): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);

        return $this->success($suggestions->forProduct($record->id));
    }

    public function compatible(string|int $product, ProductVisibilityService $visibility): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);

        return $this->success(
            Schema::hasTable('product_compatibility') && Schema::hasColumn('product_compatibility', 'product_id')
                ? DB::table('product_compatibility')
                    ->where('product_id', $record->id)
                    ->when(
                        Schema::hasColumn('product_compatibility', 'compatible_product_id'),
                        fn ($query) => $query->whereIn('compatible_product_id', Product::query()->published()->select('products.id'))
                    )
                    ->get()
                : []
        );
    }

    public function related(string|int $product, ProductVisibilityService $visibility, GenericProductSuggestionService $suggestions): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);

        return $this->success($suggestions->forProduct($record->id, 'related'));
    }

    public function accessories(string|int $product, ProductVisibilityService $visibility, GenericProductSuggestionService $suggestions): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);

        return $this->success($suggestions->forProduct($record->id, 'accessory'));
    }

    public function stock(string|int $product, ProductVisibilityService $visibility, RegionStockService $stock): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);

        return $this->success($stock->publicStock($record->id));
    }

    public function stockMarketplace(string|int $product, int $marketplace, ProductVisibilityService $visibility, RegionStockService $stock): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);

        return $this->success($stock->publicStock($record->id, ['marketplace_id' => $marketplace]));
    }

    public function stockRegion(string|int $product, int $region, ProductVisibilityService $visibility, RegionStockService $stock): JsonResponse
    {
        $record = $this->productOrFail($product, $visibility);

        return $this->success($stock->publicStock($record->id, ['region_id' => $region]));
    }

    private function documents(int $productId, string $table): mixed
    {
        if (Schema::hasTable($table)) {
            return DB::table($table)->where('product_id', $productId)->whereIn('status', ['approved', 'active'])->latest('id')->get();
        }

        return Schema::hasTable('product_documents') ? DB::table('product_documents')->where('id', -1)->get() : [];
    }

    private function productOrFail(string|int $product, ProductVisibilityService $visibility): object
    {
        $record = $visibility->resolve($product);
        abort_if(! $record, 404);

        return $record;
    }
}
