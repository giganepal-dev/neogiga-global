<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductImage;
use App\Services\Product\ProductImageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductImageAdminController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly ProductImageManager $images) {}

    public function index(Product $product): JsonResponse
    {
        return $this->success($this->images->images($product)->map(fn (ProductImage $image) => $this->images->serialize($image)));
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(array_merge([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'file', 'max:'.ProductImageManager::MAX_UPLOAD_KB, 'mimes:jpg,jpeg,png,webp,avif', 'mimetypes:image/jpeg,image/png,image/webp,image/avif'],
        ], $this->metadataRules()));
        $created = $this->images->upload($product, $data['images'], $data, $request->user()?->id);

        return $this->success($created->map(fn (ProductImage $image) => $this->images->serialize($image)), 201);
    }

    public function update(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        $data = $request->validate(array_merge($this->metadataRules(), [
            'image' => ['nullable', 'file', 'max:'.ProductImageManager::MAX_UPLOAD_KB, 'mimes:jpg,jpeg,png,webp,avif', 'mimetypes:image/jpeg,image/png,image/webp,image/avif'],
        ]));
        $updated = $this->images->update($product, $image, $data, $request->file('image'), $request->user()?->id);

        return $this->success($this->images->serialize($updated));
    }

    public function reorder(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'image_ids' => ['required', 'array', 'min:1'],
            'image_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        return $this->success($this->images->reorder($product, $data['image_ids'], $request->user()?->id)
            ->map(fn (ProductImage $image) => $this->images->serialize($image)));
    }

    public function primary(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        return $this->success($this->images->serialize($this->images->setPrimary($product, $image, $request->user()?->id)));
    }

    public function destroy(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        $updated = $this->images->deactivate($product, $image, $request->user()?->id);

        return $this->success(['deactivated' => true, 'image' => $this->images->serialize($updated)]);
    }

    private function metadataRules(): array
    {
        return [
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:4000'],
            'source_page_url' => ['nullable', 'url', 'max:4000'],
            'source_license' => ['nullable', 'string', 'max:500'],
            'license_note' => ['nullable', 'string', 'max:2000'],
            'confidence_level' => ['nullable', 'string', 'max:120'],
            'data_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'region_visibility' => ['nullable', 'array'],
            'region_visibility.*' => ['string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
