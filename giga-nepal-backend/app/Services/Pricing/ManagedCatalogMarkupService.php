<?php

namespace App\Services\Pricing;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceProductPrice;
use App\Models\Marketplace\RegionalPriceHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Applies a transparent markup only to importer-managed catalog prices.
 *
 * Seller and manually maintained price rows are intentionally excluded. The
 * stored regional cost is treated as the established landed-cost basis; this
 * service does not invent freight, tax, duty, FX, or a source price.
 */
class ManagedCatalogMarkupService
{
    private const VERSION = 'managed-catalog-markup-v1';

    private const MANAGED_SOURCES = [
        'regional_bootstrap',
        'regional_markup_plan',
        'jlcpcb_parts_database',
        'CDFER JLCPCB/LCSC in-stock SQLite',
        'ElecForest',
    ];

    /** @return array<string, mixed> */
    public function plan(float $markupPercent, int $chunkSize = 500): array
    {
        $this->assertInput($markupPercent, $chunkSize);
        $marketplaces = $this->activeMarketplaces();
        $summary = $this->emptySummary();
        $digest = hash_init('sha256');

        $this->eachGlobalCostPrice($chunkSize, function (Collection $globalPrices) use ($marketplaces, $markupPercent, &$summary, $digest): void {
            $pricesByProduct = $this->pricesForProducts($globalPrices->pluck('product_id')->all());

            foreach ($globalPrices as $globalPrice) {
                $summary['source_backed_products']++;
                $productId = (int) $globalPrice->product_id;
                $existing = $pricesByProduct->get($productId, collect());

                foreach ($marketplaces as $marketplace) {
                    $target = $existing->get((int) $marketplace->id);
                    $decision = $this->decision($globalPrice, $marketplace, $target, $markupPercent);
                    $summary[$decision['bucket']]++;
                    hash_update($digest, json_encode([
                        'product_id' => $productId,
                        'marketplace_id' => (int) $marketplace->id,
                        'existing_price_id' => $target?->id,
                        'existing_updated_at' => $target?->updated_at?->toIso8601String(),
                        'decision' => $decision,
                    ], JSON_THROW_ON_ERROR));
                }
            }
        });

        $stablePlan = [
            'version' => self::VERSION,
            'markup_percent' => $markupPercent,
            'chunk_size' => $chunkSize,
            'marketplaces' => $marketplaces->map(fn (Marketplace $marketplace) => [
                'id' => $marketplace->id,
                'code' => $marketplace->code,
                'currency' => $marketplace->currency?->code,
                'exchange_rate' => $marketplace->currency?->exchange_rate,
            ])->values()->all(),
            'summary' => $summary,
            'data_digest' => hash_final($digest),
        ];

        return $stablePlan + [
            'plan_hash' => hash('sha256', json_encode($stablePlan, JSON_THROW_ON_ERROR)),
            'dry_run' => true,
            'generated_at' => now()->toIso8601String(),
            'disclaimer' => 'Pricing is calculated from the existing stored cost basis only. It does not assert that supplier data is an official manufacturer price or that freight, duty, tax, and FX are current.',
        ];
    }

    /** @return array<string, mixed> */
    public function apply(float $markupPercent, string $expectedPlanHash, string $backupReference, int $chunkSize = 500): array
    {
        $this->assertInput($markupPercent, $chunkSize);
        if (! preg_match('/^[a-f0-9]{64}$/', strtolower(trim($expectedPlanHash)))) {
            throw new RuntimeException('A current 64-character --expected-plan-hash is required.');
        }
        if (! is_dir($backupReference) || ! is_readable($backupReference)) {
            throw new RuntimeException('A readable verified --backup-reference directory is required.');
        }

        $plan = $this->plan($markupPercent, $chunkSize);
        if (! hash_equals((string) $plan['plan_hash'], strtolower(trim($expectedPlanHash)))) {
            throw new RuntimeException('The catalog changed after the dry run. Refusing to apply a stale plan.');
        }

        $marketplaces = $this->activeMarketplaces();
        $result = $this->emptySummary();

        $this->eachGlobalCostPrice($chunkSize, function (Collection $globalPrices) use ($marketplaces, $markupPercent, &$result): void {
            DB::transaction(function () use ($globalPrices, $marketplaces, $markupPercent, &$result): void {
                $pricesByProduct = $this->pricesForProducts($globalPrices->pluck('product_id')->all(), true);

                foreach ($globalPrices as $globalPrice) {
                    $productId = (int) $globalPrice->product_id;
                    $result['source_backed_products']++;

                    foreach ($marketplaces as $marketplace) {
                        $target = $pricesByProduct->get($productId, collect())->get((int) $marketplace->id);
                        $decision = $this->decision($globalPrice, $marketplace, $target, $markupPercent);
                        $result[$decision['bucket']]++;

                        if ($decision['bucket'] === 'rows_to_update') {
                            $this->updatePrice($target, $decision, $markupPercent);
                        } elseif ($decision['bucket'] === 'rows_to_create') {
                            $this->createPrice($globalPrice, $marketplace, $decision, $markupPercent);
                        }
                    }
                }
            }, 3);
        });

        return [
            'status' => 'completed',
            'version' => self::VERSION,
            'markup_percent' => $markupPercent,
            'plan_hash' => $plan['plan_hash'],
            'backup_reference' => $backupReference,
            'result' => $result,
            'completed_at' => now()->toIso8601String(),
        ];
    }

    /** @return Collection<int, Marketplace> */
    private function activeMarketplaces(): Collection
    {
        return Marketplace::query()
            ->with('currency:id,code,exchange_rate')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    private function eachGlobalCostPrice(int $chunkSize, callable $callback): void
    {
        $globalId = Marketplace::query()->where('global_fallback', true)->value('id');
        if (! $globalId) {
            throw new RuntimeException('The active GLOBAL fallback marketplace is required.');
        }

        MarketplaceProductPrice::query()
            ->where('marketplace_id', $globalId)
            ->whereNull('product_variant_id')
            ->where('is_active', true)
            ->whereNotNull('cost_price')
            ->where('cost_price', '>', 0)
            ->orderBy('id')
            ->chunkById($chunkSize, $callback);
    }

    /** @return Collection<int, Collection<int, MarketplaceProductPrice>> */
    private function pricesForProducts(array $productIds, bool $lock = false): Collection
    {
        return MarketplaceProductPrice::query()
            ->whereIn('product_id', $productIds)
            ->whereNull('product_variant_id')
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->get()
            ->groupBy('product_id')
            ->map(fn (Collection $prices) => $prices->keyBy('marketplace_id'));
    }

    /** @return array{bucket:string,cost_price:?float,base_price:?float,currency_code:?string,reason:string} */
    private function decision(MarketplaceProductPrice $globalPrice, Marketplace $marketplace, ?MarketplaceProductPrice $target, float $markupPercent): array
    {
        if ($target && ! $this->isManaged($target)) {
            return ['bucket' => 'rows_skipped_manual', 'cost_price' => null, 'base_price' => null, 'currency_code' => null, 'reason' => 'manual_or_seller_price'];
        }

        $cost = $target ? (float) $target->cost_price : $this->regionalCost($globalPrice, $marketplace);
        $currency = $target?->currency_code ?: $marketplace->currency?->code;
        if ($cost <= 0 || ! $currency) {
            return ['bucket' => 'rows_skipped_missing_cost_or_rate', 'cost_price' => null, 'base_price' => null, 'currency_code' => null, 'reason' => 'missing_stored_cost_or_currency_rate'];
        }

        $basePrice = round($cost * (1 + ($markupPercent / 100)), 8);
        if ($target && abs((float) $target->base_price - $basePrice) < 0.00000001 && empty($target->sale_price)) {
            return ['bucket' => 'rows_unchanged', 'cost_price' => $cost, 'base_price' => $basePrice, 'currency_code' => $currency, 'reason' => 'already_at_requested_markup'];
        }

        return [
            'bucket' => $target ? 'rows_to_update' : 'rows_to_create',
            'cost_price' => $cost,
            'base_price' => $basePrice,
            'currency_code' => $currency,
            'reason' => 'stored_landed_cost_markup_'.$this->percentLabel($markupPercent),
        ];
    }

    private function regionalCost(MarketplaceProductPrice $globalPrice, Marketplace $marketplace): float
    {
        if ((int) $globalPrice->marketplace_id === (int) $marketplace->id) {
            return (float) $globalPrice->cost_price;
        }

        $rate = (float) ($marketplace->currency?->exchange_rate ?? 0);

        return $rate > 0 ? round((float) $globalPrice->cost_price * $rate, 8) : 0.0;
    }

    private function isManaged(MarketplaceProductPrice $price): bool
    {
        return in_array((string) $price->source_name, self::MANAGED_SOURCES, true)
            || str_starts_with((string) $price->pricing_rule, 'source_')
            || str_starts_with((string) $price->pricing_rule, 'stored_landed_cost_markup_');
    }

    /** @param array{cost_price:float,base_price:float,currency_code:string,reason:string} $decision */
    private function updatePrice(MarketplaceProductPrice $price, array $decision, float $markupPercent): void
    {
        $oldBasePrice = (float) $price->base_price;
        $oldSalePrice = $price->sale_price === null ? null : (float) $price->sale_price;
        $price->update([
            'base_price' => $decision['base_price'],
            'sale_price' => null,
            'pricing_rule' => $decision['reason'],
            'updated_at' => now(),
        ]);
        $this->history($price->fresh(), $oldBasePrice, $oldSalePrice, $decision['reason']);
    }

    /** @param array{cost_price:float,base_price:float,currency_code:string,reason:string} $decision */
    private function createPrice(MarketplaceProductPrice $globalPrice, Marketplace $marketplace, array $decision, float $markupPercent): void
    {
        $price = MarketplaceProductPrice::create([
            'product_id' => $globalPrice->product_id,
            'marketplace_id' => $marketplace->id,
            'base_price' => $decision['base_price'],
            'sale_price' => null,
            'cost_price' => $decision['cost_price'],
            'currency_code' => $decision['currency_code'],
            'is_tax_inclusive' => false,
            'tax_rate' => null,
            'is_active' => true,
            'source_name' => 'regional_markup_plan',
            'source_url' => $globalPrice->source_url,
            'source_file' => $globalPrice->source_file,
            'source_page_url' => $globalPrice->source_page_url,
            'downloaded_at' => $globalPrice->downloaded_at,
            'imported_at' => now(),
            'data_year' => $globalPrice->data_year,
            'license_note' => $globalPrice->license_note,
            'confidence_level' => $globalPrice->confidence_level,
            'original_raw_value' => $globalPrice->original_raw_value,
            'normalized_value' => $globalPrice->normalized_value,
            'pricing_rule' => $decision['reason'],
            'source_review_status' => $globalPrice->source_review_status,
        ]);
        $this->history($price, null, null, $decision['reason']);
    }

    private function history(MarketplaceProductPrice $price, ?float $oldBasePrice, ?float $oldSalePrice, string $reason): void
    {
        RegionalPriceHistory::create([
            'marketplace_product_price_id' => $price->id,
            'product_id' => $price->product_id,
            'marketplace_id' => $price->marketplace_id,
            'old_base_price' => $oldBasePrice,
            'new_base_price' => $price->base_price,
            'old_sale_price' => $oldSalePrice,
            'new_sale_price' => null,
            'currency_code' => $price->currency_code,
            'reason' => $reason,
        ]);
    }

    /** @return array<string, int> */
    private function emptySummary(): array
    {
        return [
            'source_backed_products' => 0,
            'rows_to_update' => 0,
            'rows_to_create' => 0,
            'rows_unchanged' => 0,
            'rows_skipped_manual' => 0,
            'rows_skipped_missing_cost_or_rate' => 0,
        ];
    }

    private function assertInput(float $markupPercent, int $chunkSize): void
    {
        if ($markupPercent < 0 || $markupPercent > 200) {
            throw new RuntimeException('Markup percent must be between 0 and 200.');
        }
        if ($chunkSize < 1 || $chunkSize > 2000) {
            throw new RuntimeException('Chunk size must be between 1 and 2000.');
        }
    }

    private function percentLabel(float $markupPercent): string
    {
        return rtrim(rtrim(number_format($markupPercent, 4, '.', ''), '0'), '.').'_percent';
    }
}
