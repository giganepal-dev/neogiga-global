<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\ProductBrand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'featured' => ['sometimes', 'boolean'],
        ]);

        $brands = ProductBrand::query()
            ->where('is_active', true)
            ->when(isset($validated['featured']), fn ($q) => $q->where('is_featured', (bool) $validated['featured']))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($validated['per_page'] ?? 25);

        return $this->success($brands);
    }

    public function show(string $slug): JsonResponse
    {
        $brand = ProductBrand::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('country')
            ->first();

        if (!$brand) {
            return $this->error('Brand not found', 404);
        }

        return $this->success($brand);
    }
}
