<?php

namespace App\Services\Product;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductImageManager
{
    public const MAX_UPLOAD_KB = 12288;

    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    /** @return Collection<int, ProductImage> */
    public function images(Product $product, bool $includeInactive = true): Collection
    {
        return $product->images()
            ->when(! $includeInactive, fn ($query) => $query->where('is_active', true))
            ->get();
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, ProductImage>
     */
    public function upload(Product $product, array $files, array $metadata, ?int $userId = null): Collection
    {
        $stored = [];
        $created = collect();
        $disk = (string) (config('filesystems.product_images_disk') ?: 'public');

        try {
            DB::transaction(function () use ($product, $files, $metadata, $userId, $disk, &$stored, &$created) {
                $hasPrimary = $product->images()->where('is_active', true)->where('is_primary', true)->lockForUpdate()->exists();
                $nextOrder = (int) $product->images()->max('sort_order');

                foreach (array_values($files) as $index => $file) {
                    $inspection = $this->inspect($file, "images.{$index}");
                    $duplicate = $product->images()->where('checksum', $inspection['checksum'])->lockForUpdate()->first();
                    if ($duplicate) {
                        throw ValidationException::withMessages([
                            "images.{$index}" => "This image is already attached as media #{$duplicate->id}.",
                        ]);
                    }

                    $path = 'product-images/'.$product->id.'/'.$inspection['checksum'].'.'.$inspection['extension'];
                    if (! Storage::disk($disk)->exists($path)) {
                        $saved = $file->storeAs(dirname($path), basename($path), $disk);
                        if (! $saved) {
                            throw ValidationException::withMessages(["images.{$index}" => 'The image could not be stored.']);
                        }
                        $stored[] = [$disk, $path];
                    }

                    $isPrimary = ! $hasPrimary && $index === 0;
                    $image = ProductImage::create($this->payload(
                        $product,
                        $metadata,
                        $inspection,
                        [
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'storage_disk' => $disk,
                            'sort_order' => ++$nextOrder,
                            'is_primary' => $isPrimary,
                            'is_active' => true,
                            'imported_at' => now(),
                            'source_name' => $metadata['source_name'] ?? 'NeoGiga admin upload',
                            'source_file' => $metadata['source_file'] ?? $file->getClientOriginalName(),
                            'confidence_level' => $metadata['confidence_level'] ?? 'admin_uploaded_unverified',
                            'original_raw_value' => $metadata['original_raw_value'] ?? $file->getClientOriginalName(),
                            'normalized_value' => $metadata['normalized_value'] ?? $path,
                            'metadata' => $this->mergeMetadata($metadata['metadata'] ?? [], [
                                'disk' => $disk,
                                'uploaded_by' => $userId,
                                'source_notes' => 'Uploaded through the permission-gated NeoGiga product media manager.',
                                'confidence_level' => $metadata['confidence_level'] ?? 'admin_uploaded_unverified',
                                'last_updated' => now()->toIso8601String(),
                                'advisory_disclaimer' => 'Advisory only',
                            ]),
                        ],
                    ));
                    $created->push($image);
                }
            }, 3);
        } catch (Throwable $exception) {
            foreach ($stored as [$failedDisk, $failedPath]) {
                Storage::disk($failedDisk)->delete($failedPath);
            }
            throw $exception;
        }

        $this->bumpCache($product->id);
        $this->audit('product_images_uploaded', $product->id, $userId, [
            'image_ids' => $created->pluck('id')->all(),
            'count' => $created->count(),
        ]);

        return $created->map(fn (ProductImage $image) => $image->fresh());
    }

    public function update(Product $product, ProductImage $image, array $data, ?UploadedFile $replacement = null, ?int $userId = null): ProductImage
    {
        $this->assertOwned($product, $image);
        $stored = null;
        $disk = (string) (config('filesystems.product_images_disk') ?: 'public');
        $inspection = null;

        if ($replacement) {
            $inspection = $this->inspect($replacement);
            $duplicate = $product->images()
                ->where('checksum', $inspection['checksum'])
                ->where('id', '<>', $image->id)
                ->first();
            if ($duplicate) {
                throw ValidationException::withMessages(['image' => "This image is already attached as media #{$duplicate->id}."]);
            }

            $path = 'product-images/'.$product->id.'/'.$inspection['checksum'].'.'.$inspection['extension'];
            if (! Storage::disk($disk)->exists($path)) {
                $saved = $replacement->storeAs(dirname($path), basename($path), $disk);
                if (! $saved) {
                    throw ValidationException::withMessages(['image' => 'The replacement image could not be stored.']);
                }
                $stored = [$disk, $path];
            }
        }

        try {
            DB::transaction(function () use ($product, $image, $data, $replacement, $inspection, $disk, $userId) {
                $locked = ProductImage::whereKey($image->id)->where('product_id', $product->id)->lockForUpdate()->firstOrFail();
                $before = $locked->only(['file_path', 'alt_text', 'caption', 'sort_order', 'is_primary', 'is_active']);
                $existingMetadata = is_array($locked->metadata) ? $locked->metadata : [];

                $payload = $this->payload($product, $data, null, [
                    'alt_text' => $data['alt_text'] ?? $locked->alt_text,
                    'caption' => $data['caption'] ?? $locked->caption,
                    'source_name' => $data['source_name'] ?? $locked->source_name,
                    'source_url' => $data['source_url'] ?? $locked->source_url,
                    'source_page_url' => $data['source_page_url'] ?? $locked->source_page_url,
                    'source_license' => $data['source_license'] ?? $locked->source_license,
                    'license_note' => $data['license_note'] ?? $locked->license_note,
                    'confidence_level' => $data['confidence_level'] ?? $locked->confidence_level,
                    'region_visibility' => $data['region_visibility'] ?? $locked->region_visibility,
                    'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $locked->is_active,
                    'metadata' => $this->mergeMetadata($existingMetadata, [
                        'updated_by' => $userId,
                        'last_updated' => now()->toIso8601String(),
                        'advisory_disclaimer' => 'Advisory only',
                    ]),
                ]);

                if ($replacement && $inspection) {
                    $payload = array_merge($payload, $inspection, [
                        'file_path' => 'product-images/'.$product->id.'/'.$inspection['checksum'].'.'.$inspection['extension'],
                        'file_name' => $replacement->getClientOriginalName(),
                        'storage_disk' => $disk,
                        'normalized_value' => 'product-images/'.$product->id.'/'.$inspection['checksum'].'.'.$inspection['extension'],
                        'original_raw_value' => $replacement->getClientOriginalName(),
                    ]);
                    $payload['metadata'] = $this->mergeMetadata($payload['metadata'] ?? [], [
                        'replaced_file_path' => $locked->file_path,
                        'replacement_preserved_previous_file' => true,
                    ]);
                }

                if (! ($payload['is_active'] ?? true)) {
                    $payload['is_primary'] = false;
                }

                $locked->update($payload);
                $this->ensurePrimary($product->id);
                $this->audit('product_image_updated', $product->id, $userId, [
                    'image_id' => $locked->id,
                    'before' => $before,
                    'after' => $locked->fresh()->only(['file_path', 'alt_text', 'caption', 'sort_order', 'is_primary', 'is_active']),
                ]);
            }, 3);
        } catch (Throwable $exception) {
            if ($stored) {
                Storage::disk($stored[0])->delete($stored[1]);
            }
            throw $exception;
        }

        $this->bumpCache($product->id);

        return $image->fresh();
    }

    /** @param array<int, int> $imageIds */
    public function reorder(Product $product, array $imageIds, ?int $userId = null): Collection
    {
        $imageIds = array_values(array_unique(array_map('intval', $imageIds)));
        $owned = $product->images()->whereIn('id', $imageIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (count($owned) !== count($imageIds)) {
            throw ValidationException::withMessages(['image_ids' => 'Every image must belong to this product.']);
        }

        DB::transaction(function () use ($product, $imageIds, $userId) {
            ProductImage::where('product_id', $product->id)->whereIn('id', $imageIds)->lockForUpdate()->get();
            foreach ($imageIds as $order => $imageId) {
                ProductImage::where('product_id', $product->id)->whereKey($imageId)->update([
                    'sort_order' => $order,
                    'updated_at' => now(),
                ]);
            }
            $this->audit('product_images_reordered', $product->id, $userId, ['image_ids' => $imageIds]);
        }, 3);

        $this->bumpCache($product->id);

        return $this->images($product);
    }

    public function setPrimary(Product $product, ProductImage $image, ?int $userId = null): ProductImage
    {
        $this->assertOwned($product, $image);
        if (! $image->is_active) {
            throw ValidationException::withMessages(['image' => 'Activate the image before making it primary.']);
        }

        DB::transaction(function () use ($product, $image, $userId) {
            ProductImage::where('product_id', $product->id)->lockForUpdate()->get();
            ProductImage::where('product_id', $product->id)->update(['is_primary' => false, 'updated_at' => now()]);
            ProductImage::where('product_id', $product->id)->whereKey($image->id)->update(['is_primary' => true, 'updated_at' => now()]);
            $this->audit('product_image_primary_set', $product->id, $userId, ['image_id' => $image->id]);
        }, 3);

        $this->bumpCache($product->id);

        return $image->fresh();
    }

    public function deactivate(Product $product, ProductImage $image, ?int $userId = null): ProductImage
    {
        $this->assertOwned($product, $image);

        DB::transaction(function () use ($product, $image, $userId) {
            $locked = ProductImage::where('product_id', $product->id)->whereKey($image->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'is_active' => false,
                'is_primary' => false,
                'metadata' => $this->mergeMetadata($locked->metadata ?? [], [
                    'deactivated_by' => $userId,
                    'deactivated_at' => now()->toIso8601String(),
                    'file_preserved' => true,
                ]),
            ]);
            $this->ensurePrimary($product->id);
            $this->audit('product_image_deactivated', $product->id, $userId, [
                'image_id' => $image->id,
                'file_preserved' => true,
            ]);
        }, 3);

        $this->bumpCache($product->id);

        return $image->fresh();
    }

    /** @return array<string, mixed> */
    public function serialize(ProductImage $image): array
    {
        return [
            'id' => $image->id,
            'product_id' => $image->product_id,
            'url' => $image->publicUrl(),
            'file_path' => $image->file_path,
            'file_name' => $image->file_name,
            'mime_type' => $image->mime_type,
            'file_size' => $image->file_size,
            'width' => $image->width,
            'height' => $image->height,
            'alt_text' => $image->alt_text,
            'caption' => $image->caption,
            'sort_order' => $image->sort_order,
            'is_primary' => $image->is_primary,
            'is_active' => $image->is_active,
            'source_name' => $image->source_name,
            'source_url' => $image->source_url,
            'source_page_url' => $image->source_page_url,
            'source_license' => $image->source_license,
            'license_note' => $image->license_note,
            'confidence_level' => $image->confidence_level,
            'region_visibility' => $image->region_visibility,
            'updated_at' => $image->updated_at?->toIso8601String(),
        ];
    }

    /** @return array{mime_type:string,width:int,height:int,file_size:int,checksum:string,extension:string} */
    private function inspect(UploadedFile $file, string $errorKey = 'image'): array
    {
        $realPath = $file->getRealPath();
        $info = $realPath ? @getimagesize($realPath) : false;
        $mime = $info['mime'] ?? $file->getMimeType();
        if (! $info || ! isset(self::MIME_EXTENSIONS[$mime])) {
            throw ValidationException::withMessages([$errorKey => 'The file is corrupt or is not a supported JPG, PNG, WebP, or AVIF image.']);
        }

        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        if ($width < 120 || $height < 120 || $width > 12000 || $height > 12000) {
            throw ValidationException::withMessages([$errorKey => 'Image dimensions must be between 120×120 and 12000×12000 pixels.']);
        }

        return [
            'mime_type' => $mime,
            'width' => $width,
            'height' => $height,
            'file_size' => (int) $file->getSize(),
            'checksum' => hash_file('sha256', $realPath),
            'extension' => self::MIME_EXTENSIONS[$mime],
        ];
    }

    private function payload(Product $product, array $data, ?array $inspection, array $defaults): array
    {
        return array_filter(array_merge([
            'product_id' => $product->id,
            'alt_text' => $data['alt_text'] ?? $product->name,
            'caption' => $data['caption'] ?? null,
            'source_name' => $data['source_name'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'source_file' => $data['source_file'] ?? null,
            'source_page_url' => $data['source_page_url'] ?? null,
            'source_license' => $data['source_license'] ?? null,
            'license_note' => $data['license_note'] ?? null,
            'confidence_level' => $data['confidence_level'] ?? null,
            'data_year' => $data['data_year'] ?? null,
            'region_visibility' => $data['region_visibility'] ?? null,
        ], $inspection ?? [], $defaults), fn ($value) => $value !== null);
    }

    private function mergeMetadata(array|string|null $current, array $changes): array
    {
        if (is_string($current)) {
            $current = json_decode($current, true) ?: [];
        }

        return array_merge(is_array($current) ? $current : [], $changes);
    }

    private function ensurePrimary(int $productId): void
    {
        if (ProductImage::where('product_id', $productId)->where('is_active', true)->where('is_primary', true)->exists()) {
            return;
        }

        $next = ProductImage::where('product_id', $productId)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->first();
        $next?->update(['is_primary' => true]);
    }

    private function assertOwned(Product $product, ProductImage $image): void
    {
        abort_unless((int) $image->product_id === (int) $product->id, 404);
    }

    private function bumpCache(int $productId): void
    {
        Cache::forever('catalog:product-media-version:'.$productId, (string) now()->getTimestampMs());
    }

    private function audit(string $action, int $productId, ?int $userId, array $values): void
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $userId,
                'action' => $action,
                'model_type' => 'product_images',
                'model_id' => $values['image_id'] ?? null,
                'model_display_name' => 'Product #'.$productId,
                'old_values' => isset($values['before']) ? json_encode($values['before']) : null,
                'new_values' => json_encode($values),
                'ip_address' => request()?->ip(),
                'user_agent' => substr((string) request()?->userAgent(), 0, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable) {
            // Media writes remain available if an optional audit table is absent.
        }
    }
}
