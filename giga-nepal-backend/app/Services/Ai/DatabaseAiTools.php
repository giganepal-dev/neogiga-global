<?php

namespace App\Services\Ai;

use App\Models\AiBomBuild;
use App\Models\LmsCodeSample;
use App\Models\LmsLesson;
use App\Models\LmsProject;
use App\Models\Marketplace\Cart;
use App\Models\Marketplace\InventoryStock;
use App\Models\Marketplace\MarketplaceProductPrice;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\VendorProductPrice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Database-backed implementation of the AI tool surface.
 *
 * Guardrail: every price/stock figure is read from the DB at call time.
 * Where the backing schema is missing (DB-02) the tool degrades honestly:
 * empty result or AiToolUnavailableException — never a fabricated value.
 */
class DatabaseAiTools implements AiToolsContract
{
    public function searchProducts(string $query, ?int $marketplaceId = null, int $limit = 10): array
    {
        $limit = max(1, min($limit, 25));

        $products = Product::query()
            ->published()
            ->search($query)
            ->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->limit($limit)
            ->get();

        return [
            'items' => $products->map(fn (Product $p) => $this->productSummary($p, $marketplaceId))->all(),
            'total' => $products->count(),
        ];
    }

    public function getProductDetails(int $productId, ?int $marketplaceId = null): ?array
    {
        $product = Product::query()
            ->published()
            ->with(['brand:id,name,slug', 'category:id,name,slug', 'images', 'specs', 'specGroups'])
            ->find($productId);

        if (! $product) {
            return null;
        }

        return $this->productSummary($product, $marketplaceId) + [
            'description' => $product->description,
            'specs' => $product->specs->map(fn ($s) => $s->only(['name', 'value', 'unit']))->all(),
            'images' => $product->images->pluck('image_path')->all(),
        ];
    }

    public function getRegionalInventory(int $productId, ?int $marketplaceId = null): array
    {
        if (! Product::query()->published()->whereKey($productId)->exists()) {
            return ['product_id' => $productId, 'total_available' => 0, 'locations' => []];
        }

        $stocks = InventoryStock::query()
            ->where('product_id', $productId)
            ->when($marketplaceId, fn ($q) => $q->where('marketplace_id', $marketplaceId))
            ->with('warehouse:id,name,code')
            ->get(['id', 'product_id', 'warehouse_id', 'marketplace_id', 'quantity_available', 'quantity_reserved']);

        return [
            'product_id' => $productId,
            'total_available' => (int) $stocks->sum('quantity_available'),
            'locations' => $stocks->map(fn ($s) => [
                'warehouse' => $s->warehouse?->only(['id', 'name', 'code']),
                'available' => (int) $s->quantity_available,
            ])->all(),
        ];
    }

    public function getRegionalPrice(int $productId, ?int $marketplaceId = null): array
    {
        $product = Product::query()->published()->find($productId);

        if (! $product) {
            return ['product_id' => $productId, 'price' => null, 'source' => null];
        }

        return ['product_id' => $productId, 'price' => $this->currentPrice($product, $marketplaceId)];
    }

    public function getPriceTiers(int $productId, ?int $marketplaceId = null): array
    {
        if (! Product::query()->published()->whereKey($productId)->exists()
            || ! Schema::hasTable('vendor_product_prices')) {
            return ['product_id' => $productId, 'tiers' => []];
        }

        $tiers = VendorProductPrice::query()
            ->where('product_id', $productId)
            ->when($marketplaceId, fn ($q) => $q->where('marketplace_id', $marketplaceId))
            ->where('is_active', true)
            ->get()
            ->map(fn ($price) => $price->only(['id', 'vendor_id', 'selling_price', 'min_price', 'currency_code']))
            ->all();

        return ['product_id' => $productId, 'tiers' => $tiers];
    }

    public function findAlternativeParts(int $productId, ?int $marketplaceId = null, int $limit = 10): array
    {
        $limit = max(1, min($limit, 25));
        $product = Product::query()->published()->find($productId);

        if (! $product) {
            return ['product_id' => $productId, 'items' => []];
        }

        $items = $product->compatibleProducts()
            ->published()
            ->limit($limit)
            ->get()
            ->map(fn (Product $item) => $this->productSummary($item, $marketplaceId))
            ->all();

        return ['product_id' => $productId, 'items' => $items];
    }

    public function createProjectBOM(string $goalDescription, array $lines, ?int $userId = null): array
    {
        if (! Schema::hasColumn('ai_bom_builds', 'goal_description')) {
            throw new AiToolUnavailableException(
                'AI BOM persistence requires the ai_bom_builds schema (pending reconciliation, DB-02).'
            );
        }

        $build = AiBomBuild::create([
            'user_id' => $userId,
            'goal_description' => Str::limit($goalDescription, 1000),
            'status' => 'draft',
        ]);

        $resolved = [];
        foreach ($lines as $line) {
            $name = (string) ($line['name'] ?? '');
            $qty = max(1, (int) ($line['quantity'] ?? 1));

            $product = $name === '' ? null : Product::query()->published()->search($name)->first();

            $resolved[] = [
                'requested' => $name,
                'quantity' => $qty,
                'product_id' => $product?->id,
                'product_name' => $product?->name,
                // Null when unresolved/unpriced — never estimated.
                'unit_price' => $product ? $this->currentPrice($product, null)['amount'] ?? null : null,
            ];
        }

        return [
            'bom_build_id' => $build->id,
            'status' => 'draft',
            'lines' => $resolved,
        ];
    }

    public function resolveBOMLine(string $requestedPart, int $quantity = 1, ?int $marketplaceId = null): array
    {
        $quantity = max(1, $quantity);
        $product = trim($requestedPart) === ''
            ? null
            : Product::query()->published()->search($requestedPart)->first();

        if (! $product) {
            return [
                'requested' => $requestedPart,
                'quantity' => $quantity,
                'product_id' => null,
                'product_name' => null,
                'price' => null,
                'inventory' => null,
                'status' => 'unresolved',
            ];
        }

        return [
            'requested' => $requestedPart,
            'quantity' => $quantity,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $this->currentPrice($product, $marketplaceId),
            'inventory' => $this->getRegionalInventory($product->id, $marketplaceId),
            'status' => 'resolved',
        ];
    }

    public function findLMSLessons(string $topic, int $limit = 5): array
    {
        if (! Schema::hasTable('lms_lessons')) {
            return [];
        }

        return LmsLesson::query()
            ->where('title', 'like', '%'.$topic.'%')
            ->limit(max(1, min($limit, 25)))
            ->get()
            ->map(fn ($lesson) => $lesson->only(['id', 'title', 'slug', 'course_id']))
            ->all();
    }

    public function findTutorials(string $topic, int $limit = 5): array
    {
        if (! Schema::hasTable('lms_projects')) {
            return [];
        }

        return LmsProject::query()
            ->where('title', 'like', '%'.$topic.'%')
            ->limit(max(1, min($limit, 25)))
            ->get()
            ->map(fn ($project) => $project->only(['id', 'title', 'slug', 'difficulty_level']))
            ->all();
    }

    public function generateSampleCode(string $topic, array $constraints = []): array
    {
        if (! Schema::hasTable('lms_code_samples')) {
            return ['items' => []];
        }

        $items = LmsCodeSample::query()
            ->where('title', 'like', '%'.$topic.'%')
            ->limit(5)
            ->get()
            ->map(fn ($sample) => $sample->only(['id', 'title', 'language', 'code', 'lms_lesson_id', 'lms_project_id']))
            ->all();

        return ['items' => $items, 'constraints' => $constraints];
    }

    public function createCart(int $userId, ?int $marketplaceId = null): array
    {
        $cart = Cart::firstOrCreate(
            ['user_id' => $userId, 'status' => 'active'],
            ['marketplace_id' => $marketplaceId],
        );

        return $cart->only(['id', 'user_id', 'marketplace_id', 'status']);
    }

    public function createCartDraft(int $userId, array $items, ?int $marketplaceId = null): array
    {
        return [
            'reference' => 'CD-'.strtoupper(Str::random(10)),
            'user_id' => $userId,
            'marketplace_id' => $marketplaceId,
            'items' => $items,
            'status' => 'draft_requires_confirmation',
            'requires_confirmation' => true,
        ];
    }

    public function updateCartDraft(string $draftReference, array $items): array
    {
        return [
            'reference' => $draftReference,
            'items' => $items,
            'status' => 'draft_requires_confirmation',
            'requires_confirmation' => true,
        ];
    }

    public function calculateTaxShipping(int $userId, array $items, ?int $marketplaceId = null): array
    {
        throw new AiToolUnavailableException(
            'Tax and shipping calculation requires the finalized regional rule engine. Offer to hand off to a human.'
        );
    }

    public function createQuote(int $userId, array $lines): array
    {
        throw new AiToolUnavailableException(
            'Quotes require the RFQ/pricing engine (Phase 1). Offer to hand off to a human instead.'
        );
    }

    public function createQuoteDraft(int $userId, array $lines): array
    {
        return [
            'reference' => 'QD-'.strtoupper(Str::random(10)),
            'user_id' => $userId,
            'lines' => $lines,
            'status' => 'draft_requires_human_review',
            'requires_human_review' => true,
        ];
    }

    public function createRFQ(int $userId, array $lines): array
    {
        throw new AiToolUnavailableException(
            'RFQ creation requires procurement workflow and explicit user confirmation.'
        );
    }

    public function createPaymentLink(int $orderId): array
    {
        throw new AiToolUnavailableException(
            'Payment links require the payment adapter layer (Phase 1). Offer to hand off to a human instead.'
        );
    }

    public function checkPaymentStatus(int $paymentId): array
    {
        throw new AiToolUnavailableException(
            'Payment status checks require the payment provider adapter and payment records.'
        );
    }

    public function createOrderAfterConfirmation(int $userId, string $confirmationReference): array
    {
        throw new AiToolUnavailableException(
            'AI order creation is disabled until explicit confirmation and admin review policies ship.'
        );
    }

    public function handoffToHuman(?int $userId, string $reason, array $context = []): array
    {
        $reference = 'HH-'.strtoupper(Str::random(10));

        // Phase 2 will push into the support/sales queue with full transcript.
        Log::channel('single')->info('AI human-handoff requested', [
            'reference' => $reference,
            'user_id' => $userId,
            'reason' => Str::limit($reason, 500),
            'context' => $context,
        ]);

        return ['reference' => $reference, 'status' => 'queued'];
    }

    /**
     * Marketplace price if one is configured — otherwise the product base
     * price — otherwise null. Straight from the DB, no fallbacks invented.
     */
    protected function currentPrice(Product $product, ?int $marketplaceId): array
    {
        $price = MarketplaceProductPrice::query()
            ->where('product_id', $product->id)
            ->when($marketplaceId, fn ($q) => $q->where('marketplace_id', $marketplaceId))
            ->where('is_active', true)
            ->first();

        if ($price) {
            return [
                'amount' => (float) ($price->sale_price ?? $price->base_price),
                'currency' => $price->currency_code,
                'source' => 'marketplace_product_prices',
            ];
        }

        if ($product->base_price !== null) {
            return [
                'amount' => (float) ($product->sale_price ?? $product->base_price),
                'currency' => null,
                'source' => 'products.base_price',
            ];
        }

        return ['amount' => null, 'currency' => null, 'source' => null];
    }

    protected function productSummary(Product $product, ?int $marketplaceId): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'brand' => $product->brand?->only(['id', 'name', 'slug']),
            'category' => $product->category?->only(['id', 'name', 'slug']),
            'price' => $this->currentPrice($product, $marketplaceId),
        ];
    }
}
