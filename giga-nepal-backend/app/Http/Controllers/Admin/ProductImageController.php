<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductImage;
use App\Services\Product\ProductImageManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function __construct(private readonly ProductImageManager $images) {}

    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate($this->uploadRules());
        $created = $this->images->upload($product, $data['images'], $data, $request->user()?->id);

        return back()->with('status', $created->count().' product image(s) uploaded.');
    }

    public function update(Request $request, Product $product, ProductImage $image): RedirectResponse
    {
        $data = $request->validate($this->metadataRules(true));
        $this->images->update($product, $image, $data, $request->file('image'), $request->user()?->id);

        return back()->with('status', 'Product image updated.');
    }

    public function reorder(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'image_ids' => ['required', 'array', 'min:1'],
            'image_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $this->images->reorder($product, $data['image_ids'], $request->user()?->id);

        return back()->with('status', 'Product image order saved.');
    }

    public function primary(Request $request, Product $product, ProductImage $image): RedirectResponse
    {
        $this->images->setPrimary($product, $image, $request->user()?->id);

        return back()->with('status', 'Primary product image updated.');
    }

    public function destroy(Request $request, Product $product, ProductImage $image): RedirectResponse
    {
        $this->images->deactivate($product, $image, $request->user()?->id);

        return back()->with('status', 'Product image deactivated; its file and audit history were preserved.');
    }

    private function uploadRules(): array
    {
        return array_merge([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'file', 'max:'.ProductImageManager::MAX_UPLOAD_KB, 'mimes:jpg,jpeg,png,webp,avif', 'mimetypes:image/jpeg,image/png,image/webp,image/avif'],
        ], $this->metadataRules());
    }

    private function metadataRules(bool $replacement = false): array
    {
        return [
            'image' => [$replacement ? 'nullable' : 'sometimes', 'file', 'max:'.ProductImageManager::MAX_UPLOAD_KB, 'mimes:jpg,jpeg,png,webp,avif', 'mimetypes:image/jpeg,image/png,image/webp,image/avif'],
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
