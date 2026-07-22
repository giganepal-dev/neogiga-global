<?php

namespace App\Services\Importers;

use App\Models\Marketplace\ImportJob;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use App\Models\Supplier\ProductSupplier;
use App\Models\Supplier\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class BaseImporter
{
    protected ImportJob $job;

    protected array $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

    protected ?Command $command = null;

    abstract public function fetchCategories(): array;

    abstract public function fetchProducts(array $options = []): \Generator;

    abstract protected function normalizeProduct(array $rawProduct): array;

    public function getSupplierSlug(): string
    {
        return $this->supplierCode ?? Str::slug($this->getSupplierName());
    }

    protected function getSupplierName(): string
    {
        return $this->supplierName ?? Str::headline($this->getSupplierSlug());
    }

    protected function getSupplierTier(): string
    {
        $tier = $this->supplierTier ?? 'tier_1';

        return is_numeric($tier) ? 'tier_'.(int) $tier : (string) $tier;
    }

    protected function getSupplierDescription(): ?string
    {
        return $this->supplierDescription ?? null;
    }

    protected function getSupplierWebsite(): ?string
    {
        return $this->baseUrl ?? null;
    }

    protected function getSupplierCountry(): ?string
    {
        return $this->supplierCountry ?? null;
    }

    public function setCommand(Command $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function run(ImportJob $job): void
    {
        $this->job = $job;
        $this->job->markAsStarted();

        try {
            $supplier = $this->importSupplier();
            $this->importCategories($this->fetchCategories());

            foreach ($this->fetchProducts() as $rawProduct) {
                try {
                    $this->processProduct($rawProduct, $supplier);
                    $this->job->processed_items++;
                    if ($this->job->processed_items % 100 === 0) {
                        $this->job->save();
                    }
                } catch (\Exception $e) {
                    $this->stats['failed']++;
                    \Log::error('Import failed', ['supplier' => $this->getSupplierSlug(), 'error' => $e->getMessage()]);
                }
            }

            $this->job->created_items = $this->stats['created'];
            $this->job->updated_items = $this->stats['updated'];
            $this->job->failed_items = $this->stats['failed'];
            $this->job->markAsCompleted();
        } catch (\Exception $e) {
            $this->job->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    protected function importSupplier(): Supplier
    {
        return Supplier::firstOrCreate(['slug' => $this->getSupplierSlug()], [
            'name' => $this->getSupplierName(),
            'tier' => $this->getSupplierTier(),
            'description' => $this->getSupplierDescription(),
            'website_url' => $this->getSupplierWebsite(),
            'country' => $this->getSupplierCountry(),
            'is_active' => true,
        ]);
    }

    protected function importCategories(array $categories): int
    {
        $imported = 0;
        foreach ($categories as $category) {
            ProductCategory::firstOrCreate(['slug' => Str::slug($category['name'])], [
                'name' => $category['name'],
                'parent_id' => $category['parent_id'] ?? null,
                'description' => $category['description'] ?? null,
            ]);
            $imported++;
        }

        return $imported;
    }

    protected function importBrands(array $brands): int
    {
        $imported = 0;
        foreach ($brands as $brand) {
            ProductBrand::firstOrCreate(['slug' => Str::slug($brand['name'])], [
                'name' => $brand['name'],
                'logo_path' => $brand['logo_url'] ?? null,
                'description' => $brand['description'] ?? null,
            ]);
            $imported++;
        }

        return $imported;
    }

    protected function importProducts(array $products): array
    {
        $result = ['imported' => 0, 'skipped' => 0];
        $supplier = Supplier::where('slug', $this->getSupplierSlug())->first();

        if (! $supplier) {
            $supplier = $this->importSupplier();
        }

        foreach ($products as $rawProduct) {
            try {
                $this->processProduct($rawProduct, $supplier);
                $result['imported']++;
            } catch (\Exception $e) {
                $result['skipped']++;
                \Log::error('Product import failed', ['supplier' => $this->getSupplierSlug(), 'error' => $e->getMessage()]);
            }
        }

        return $result;
    }

    protected function processProduct(array $rawProduct, Supplier $supplier): void
    {
        $normalized = $this->normalizeProduct($rawProduct);
        $existingProduct = $this->findDuplicateProduct($normalized, $supplier);

        DB::beginTransaction();
        try {
            if ($existingProduct) {
                $this->updateExistingProduct($existingProduct, $normalized, $supplier);
                $this->stats['updated']++;
            } else {
                $this->createNewProduct($normalized, $supplier);
                $this->stats['created']++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function findDuplicateProduct(array $normalized, Supplier $supplier): ?Product
    {
        if (! empty($normalized['mpn'])) {
            $existingSupplier = ProductSupplier::where('mpn', $normalized['mpn'])
                ->where('supplier_id', '!=', $supplier->id)->first();
            if ($existingSupplier) {
                return $existingSupplier->product;
            }
        }
        if (! empty($normalized['sku'])) {
            return Product::where('sku', $normalized['sku'])->first();
        }

        return null;
    }

    protected function createNewProduct(array $normalized, Supplier $supplier): Product
    {
        $product = Product::create([
            'name' => $normalized['name'],
            'slug' => Str::slug($normalized['name']).'-'.Str::random(6),
            'sku' => $normalized['sku'],
            'mpn' => $normalized['mpn'] ?? null,
            'brand_id' => $this->getOrCreateBrand($normalized['brand'] ?? null),
            'category_id' => $this->getOrCreateCategory($normalized['category'] ?? null),
            'description' => $normalized['description'] ?? null,
            'short_description' => $normalized['short_description'] ?? null,
            'status' => 'draft',
            'attributes' => $normalized['attributes'] ?? [],
            'metadata' => $normalized['metadata'] ?? [],
        ]);

        ProductSupplier::create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'supplier_sku' => $normalized['supplier_sku'] ?? null,
            'mpn' => $normalized['mpn'] ?? null,
            'upc_ean' => $normalized['upc_ean'] ?? null,
            'cost_price' => $normalized['cost_price'] ?? null,
            'currency' => $normalized['currency'] ?? 'USD',
            'is_primary' => true,
        ]);

        if (! empty($normalized['images'])) {
            $this->importImages($product, $normalized['images']);
        }
        if (! empty($normalized['resources'])) {
            $this->importResources($product, $normalized['resources']);
        }

        return $product;
    }

    protected function updateExistingProduct(Product $product, array $normalized, Supplier $supplier): void
    {
        $product->update([
            'name' => $normalized['name'] ?? $product->name,
            'description' => $normalized['description'] ?? $product->description,
            'attributes' => array_merge($product->attributes ?? [], $normalized['attributes'] ?? []),
        ]);

        ProductSupplier::updateOrCreate(
            ['product_id' => $product->id, 'supplier_id' => $supplier->id],
            ['supplier_sku' => $normalized['supplier_sku'] ?? null, 'mpn' => $normalized['mpn'] ?? null,
                'cost_price' => $normalized['cost_price'] ?? null, 'last_synced_at' => now()]
        );
    }

    protected function getOrCreateBrand(?string $brandName): ?int
    {
        if (empty($brandName)) {
            return null;
        }
        $brand = ProductBrand::firstOrCreate(['slug' => Str::slug($brandName)], ['name' => $brandName]);

        return $brand->id;
    }

    protected function getOrCreateCategory(?string $categoryName): ?int
    {
        if (empty($categoryName)) {
            return null;
        }

        $category = ProductCategory::query()
            ->where('slug', Str::slug($categoryName))
            ->first();

        if ($category) {
            return $category->id;
        }

        // Supplier labels may not become public roots implicitly. Preserve the
        // product and route unresolved taxonomy through NeoGiga's review queue.
        return ProductCategory::query()
            ->whereIn('slug', config('neogiga_categories.review_slugs', []))
            ->orderByRaw("CASE WHEN slug = '205-needs-review' THEN 0 ELSE 1 END")
            ->value('id');
    }

    protected function importImages(Product $product, array $images): void
    {
        foreach ($images as $index => $imageUrl) {
            try {
                $response = Http::get($imageUrl);
                if ($response->successful()) {
                    $filename = 'product-'.$product->id.'-'.time().'-'.$index.'.jpg';
                    Storage::disk('public')->put('products/'.$filename, $response->body());
                    $product->images()->create(['file_path' => 'products/'.$filename, 'sort_order' => $index, 'is_primary' => $index === 0]);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to download image', ['url' => $imageUrl, 'error' => $e->getMessage()]);
            }
        }
    }

    protected function importResources(Product $product, array $resources): void
    {
        foreach ($resources as $resource) {
            try {
                if (! empty($resource['external_url'])) {
                    $product->resources()->create($resource);
                } elseif (! empty($resource['file_url'])) {
                    $response = Http::get($resource['file_url']);
                    if ($response->successful()) {
                        $filename = basename(parse_url($resource['file_url'], PHP_URL_PATH));
                        $path = Storage::disk('public')->put('resources/'.$product->id, $response->body());
                        $product->resources()->create(array_merge($resource, ['file_path' => $path, 'file_name' => $filename]));
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to import resource', ['resource' => $resource, 'error' => $e->getMessage()]);
            }
        }
    }
}
