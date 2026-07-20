<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Services\Reseller\ResellerContextService;
use App\Services\Reseller\ResellerProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResellerProductController extends Controller
{
    use ApiResponses;

    public function index(Request $request, ResellerContextService $context): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());

        return $this->success(
            Product::query()->where('reseller_id', $reseller->id)->latest()->paginate(25)
        );
    }

    public function matchMpn(Request $request, ResellerContextService $context, ResellerProductService $products): JsonResponse
    {
        $context->abortUnlessReseller($request->user());
        $data = $request->validate(['mpn' => ['required', 'string', 'max:120']]);
        $match = $products->matchByMpn($data['mpn']);

        return $this->success([
            'matched' => (bool) $match,
            'product' => $match?->only(['id', 'name', 'sku', 'mpn', 'status']),
        ]);
    }

    public function store(Request $request, ResellerContextService $context, ResellerProductService $products): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'mpn' => ['nullable', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:120'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'link_existing' => ['nullable', 'boolean'],
        ]);

        return $this->success($products->createListing($reseller, $data), 201);
    }

    public function import(Request $request, ResellerContextService $context, ResellerProductService $products): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());
        $data = $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);

        return $this->success($products->importCsv($reseller, $data['file']));
    }
}
