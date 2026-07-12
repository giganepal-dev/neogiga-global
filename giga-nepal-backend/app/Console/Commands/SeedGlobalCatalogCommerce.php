<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedGlobalCatalogCommerce extends Command
{
    protected $signature = 'catalog:seed-global-commerce
        {--apply : Persist source-backed prices, warehouse stock, and delivery-zone drafts}
        {--regional-sample-size=500 : Product count allocated to each regional warehouse}
        {--regional-sample-quantity=2 : Quantity allocated to each regional sample product}';

    protected $description = 'Seed Global LCSC/JLCPCB source prices at cost + 5%, Shenzhen stock, regional stock samples, and inactive delivery-zone drafts.';

    private const SOURCE_CODE = 'jlcpcb_parts_database';
    private const MARKUP = 1.05;
    private const LOCK_KEY = 34712026;

    public function handle(): int
    {
        $global = DB::table('marketplaces as m')
            ->join('currencies as c', 'c.id', '=', 'm.currency_id')
            ->whereRaw('LOWER(m.code) = ?', ['global'])
            ->where('m.is_active', true)
            ->select('m.id', 'm.name', 'm.country_id', 'c.code as currency_code')
            ->first();
        $source = DB::table('catalog_sources')->where('code', self::SOURCE_CODE)->where('active', true)->first();

        if (! $global || ! $source) {
            $this->error('Global marketplace or active JLCPCB/LCSC catalog source is unavailable.');

            return self::FAILURE;
        }
        if (strtoupper((string) $global->currency_code) !== 'USD') {
            $this->error('The Global marketplace must use USD for the imported USD source price rule.');

            return self::FAILURE;
        }

        $sourceOfferCount = DB::table('catalog_distributor_offers')
            ->where('currency', 'USD')->whereNotNull('price_breaks')->count();
        $productCount = DB::table('products')->count();
        $sampleSize = max(0, (int) $this->option('regional-sample-size'));
        $sampleQuantity = max(0, (int) $this->option('regional-sample-quantity'));

        $this->table(['Metric', 'Value'], [
            ['Catalog products', number_format($productCount)],
            ['USD LCSC/JLCPCB source offers', number_format($sourceOfferCount)],
            ['Pricing rule', 'lowest valid source quantity break x 1.05'],
            ['Shenzhen stock', '10 units for every product'],
            ['Regional sample', "{$sampleSize} products x {$sampleQuantity} units per warehouse"],
            ['Delivery zones', 'operator-supplied fees; unspecified service times remain unconfirmed'],
        ]);

        if (! $this->option('apply')) {
            $this->warn('Dry run only. Re-run with --apply to write data.');

            return self::SUCCESS;
        }

        $this->withLock(function () use ($global, $source, $sampleSize, $sampleQuantity): void {
            $countries = [
                'CN' => $this->country('China', 'CN', 'CHN', 'CNY'),
                'NP' => $this->country('Nepal', 'NP', 'NPL', 'NPR'),
                'IN' => $this->country('India', 'IN', 'IND', 'INR'),
                'AE' => $this->country('United Arab Emirates', 'AE', 'ARE', 'AED'),
            ];
            $marketplaces = $this->marketplaces();
            $warehouses = $this->warehouses($global, $marketplaces, $countries);

            $priceStats = $this->seedSourcePrices($global, $source);
            $shenzhenStats = $this->seedStock($warehouses['shenzhen'], $global, $countries['CN'], 10, null);
            $regionalStats = [];
            foreach (['kathmandu', 'new_delhi', 'dubai'] as $warehouseKey) {
                $regionalStats[$warehouseKey] = $this->seedStock(
                    $warehouses[$warehouseKey],
                    (object) ['id' => $warehouses[$warehouseKey]['marketplace_id']],
                    (int) $warehouses[$warehouseKey]['country_id'],
                    $sampleQuantity,
                    $sampleSize,
                );
            }
            $draftZones = $this->seedDeliveryZoneDrafts($marketplaces);

            $this->table(['Operation', 'Created', 'Updated', 'Skipped'], [
                ['Global source prices', $priceStats['created'], $priceStats['updated'], $priceStats['skipped']],
                ['Shenzhen stock', $shenzhenStats['created'], $shenzhenStats['updated'], $shenzhenStats['skipped']],
                ['Kathmandu sample stock', $regionalStats['kathmandu']['created'], $regionalStats['kathmandu']['updated'], $regionalStats['kathmandu']['skipped']],
                ['New Delhi sample stock', $regionalStats['new_delhi']['created'], $regionalStats['new_delhi']['updated'], $regionalStats['new_delhi']['skipped']],
                ['Dubai sample stock', $regionalStats['dubai']['created'], $regionalStats['dubai']['updated'], $regionalStats['dubai']['skipped']],
                ['Inactive delivery-zone drafts', $draftZones['created'], $draftZones['updated'], 0],
            ]);
        });

        $this->info('Completed. Source offers retain their original review status; prices are source-backed LCSC/JLCPCB values, not manufacturer price claims.');

        return self::SUCCESS;
    }

    private function seedSourcePrices(object $global, object $source): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        DB::table('catalog_distributor_offers')->where('currency', 'USD')->whereNotNull('price_breaks')->orderBy('id')->chunkById(1000, function ($offers) use ($global, $source, &$stats): void {
            $offerPrices = [];
            foreach ($offers as $offer) {
                $sourceBreak = $this->lowestPriceBreak($offer->price_breaks);
                if ($sourceBreak === null) {
                    $stats['skipped']++;
                    continue;
                }
                $offerPrices[$offer->product_id] = [$offer, $sourceBreak['price'], $sourceBreak['quantity']];
            }
            if ($offerPrices === []) {
                return;
            }

            $existing = DB::table('marketplace_product_prices')
                ->where('marketplace_id', $global->id)
                ->whereNull('product_variant_id')
                ->whereIn('product_id', array_keys($offerPrices))
                ->orderBy('id')
                ->get()->keyBy('product_id');
            $inserts = [];
            $updates = [];
            $now = now();
            foreach ($offerPrices as $productId => [$offer, $sourcePrice, $sourceQuantity]) {
                $payload = [
                    'product_id' => $productId,
                    'product_variant_id' => null,
                    'marketplace_id' => $global->id,
                    'base_price' => round($sourcePrice * self::MARKUP, 4),
                    'sale_price' => null,
                    'cost_price' => $sourcePrice,
                    'currency_code' => 'USD',
                    'is_tax_inclusive' => false,
                    'tax_rate' => null,
                    'is_active' => true,
                    'source_name' => self::SOURCE_CODE,
                    'source_url' => $source->source_url,
                    'source_offer_id' => $offer->id,
                    'source_fetched_at' => $offer->fetched_at,
                    'source_unit_price' => $sourcePrice,
                    'pricing_rule' => "source_minimum_quantity_{$sourceQuantity}_price_x_1_05",
                    'source_review_status' => $offer->review_status,
                    'updated_at' => $now,
                ];
                if ($row = $existing->get($productId)) {
                    $updates[] = ['id' => $row->id] + $payload;
                } else {
                    $inserts[] = $payload + ['created_at' => $now];
                }
            }
            if ($inserts) {
                DB::table('marketplace_product_prices')->insert($inserts);
                $stats['created'] += count($inserts);
            }
            if ($updates) {
                DB::table('marketplace_product_prices')->upsert($updates, ['id'], [
                    'base_price', 'sale_price', 'cost_price', 'currency_code', 'is_tax_inclusive', 'tax_rate', 'is_active',
                    'source_name', 'source_url', 'source_offer_id', 'source_fetched_at', 'source_unit_price', 'pricing_rule', 'source_review_status', 'updated_at',
                ]);
                $stats['updated'] += count($updates);
            }
        }, 'id');

        return $stats;
    }

    private function seedStock(array $warehouse, object $marketplace, int $countryId, int $quantity, ?int $limit): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $optionalColumns = [];
        foreach (['country_id' => $countryId, 'quote_only' => false, 'status' => 'active', 'quantity_on_hand' => $quantity] as $column => $value) {
            if (Schema::hasColumn('inventory_stocks', $column)) {
                $optionalColumns[$column] = $value;
            }
        }
        $query = DB::table('products')->select('id', 'sku')->orderBy('id');
        if ($limit !== null) {
            // chunkById rewrites a query limit while it advances its cursor. Bound the
            // candidate IDs first so regional samples cannot expand to the full catalog.
            $sampleIds = DB::table('products')->orderBy('id')->limit($limit)->pluck('id');
            $query->whereIn('id', $sampleIds);
        }
        $query->chunkById(1000, function ($products) use ($warehouse, $marketplace, $quantity, $optionalColumns, &$stats): void {
            $existing = DB::table('inventory_stocks')->where('warehouse_id', $warehouse['id'])
                ->whereIn('product_id', $products->pluck('id'))->orderBy('id')->get()->keyBy('product_id');
            $inserts = [];
            $updates = [];
            $now = now();
            foreach ($products as $product) {
                $payload = [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'warehouse_id' => $warehouse['id'],
                    'marketplace_id' => $marketplace->id,
                    'sku' => $product->sku ?: 'NG-'.$product->id,
                    'quantity_available' => $quantity,
                    'quantity_reserved' => 0,
                    'quantity_damaged' => 0,
                    'quantity_incoming' => 0,
                    'reorder_point' => 1,
                    'is_active' => true,
                    'metadata' => json_encode(['source_name' => 'operator_catalog_stock_seed', 'source_url' => null, 'source_file' => null, 'source_page_url' => null, 'downloaded_at' => null, 'imported_at' => now()->toIso8601String(), 'data_year' => now()->year, 'license_note' => 'Operator-directed catalog availability seed', 'confidence_level' => 'operator_directed', 'original_raw_value' => $quantity, 'normalized_value' => $quantity]),
                    'updated_at' => $now,
                ];
                foreach ($optionalColumns as $column => $value) {
                    $payload[$column] = $value;
                }
                if ($row = $existing->get($product->id)) {
                    $updates[] = ['id' => $row->id] + $payload;
                } else {
                    $inserts[] = $payload + ['created_at' => $now];
                }
            }
            if ($inserts) {
                DB::table('inventory_stocks')->insert($inserts);
                $stats['created'] += count($inserts);
            }
            if ($updates) {
                DB::table('inventory_stocks')->upsert($updates, ['id'], array_keys(array_diff_key($updates[0], ['id' => true])));
                $stats['updated'] += count($updates);
            }
        }, 'id');

        return $stats;
    }

    private function warehouses(object $global, array $marketplaces, array $countries): array
    {
        return [
            'shenzhen' => $this->warehouse('Shenzhen China', 'NG-SHENZHEN-CN', $global->id, $countries['CN']),
            'kathmandu' => $this->warehouse('Kathmandu Nepal', 'NG-KATHMANDU-NP', $marketplaces['NEPAL']->id, $countries['NP']),
            'new_delhi' => $this->warehouse('New Delhi India', 'NG-NEWDELHI-IN', $marketplaces['INDIA']->id, $countries['IN']),
            'dubai' => $this->warehouse('Dubai UAE', 'NG-DUBAI-AE', $global->id, $countries['AE']),
        ];
    }

    private function warehouse(string $name, string $code, int $marketplaceId, int $countryId): array
    {
        $now = now();
        DB::table('warehouses')->updateOrInsert(['code' => $code], [
            'name' => $name, 'marketplace_id' => $marketplaceId, 'country_id' => $countryId, 'address_line1' => $name,
            'is_active' => true, 'is_default' => false, 'metadata' => json_encode(['source_name' => 'operator_configuration', 'confidence_level' => 'operator_directed']), 'updated_at' => $now, 'created_at' => $now,
        ]);

        return (array) DB::table('warehouses')->where('code', $code)->first(['id', 'marketplace_id', 'country_id']);
    }

    private function country(string $name, string $iso2, string $iso3, string $currency): int
    {
        $country = DB::table('countries')->where('iso_code_2', $iso2)->first();
        if ($country) {
            return $country->id;
        }

        return DB::table('countries')->insertGetId([
            'name' => $name, 'iso_code_2' => $iso2, 'iso_code_3' => $iso3, 'currency_code' => $currency, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function marketplaces(): array
    {
        $rows = DB::table('marketplaces')
            ->whereIn(DB::raw('UPPER(code)'), ['NEPAL', 'INDIA', 'UAE', 'UNITEDARABEMIRATES'])
            ->get()
            ->keyBy(fn ($row) => strtoupper($row->code));
        if (! isset($rows['UAE']) && isset($rows['UNITEDARABEMIRATES'])) {
            $rows['UAE'] = $rows['UNITEDARABEMIRATES'];
        }
        foreach (['NEPAL', 'INDIA'] as $code) {
            if (! isset($rows[$code])) {
                throw new \RuntimeException("{$code} marketplace is required for regional warehouse seeding.");
            }
        }

        return $rows->all();
    }

    private function seedDeliveryZoneDrafts(array $marketplaces): array
    {
        $stats = ['created' => 0, 'updated' => 0];
        if (! Schema::hasTable('delivery_zones')) {
            return $stats;
        }
        $definitions = [
            'GLOBAL' => [
                ['Global Economy', 'GLOBAL-ECONOMY', 5, 10, 15, true, 'Worldwide economy'],
                ['Global Express', 'GLOBAL-EXPRESS', 15, 3, 7, true, 'Worldwide express'],
            ],
            'INDIA' => [
                ['India', 'INDIA', 0, 0, 0, false, 'Rate not supplied'],
                ['India Domestic', 'INDIA-DOMESTIC', 0, 0, 0, false, 'Rate not supplied'],
                ['India Express', 'INDIA-EXPRESS', 100, 2, 4, true, 'India warehouse express'],
            ],
            'NEPAL' => [
                ['Nepal', 'NEPAL', 150, 0, 0, true, 'Entire Nepal; delivery time not supplied'],
                ['Nepal Domestic', 'NEPAL-DOMESTIC', 0, 0, 0, false, 'Rate not supplied'],
                ['Nepal Express', 'NEPAL-EXPRESS', 0, 0, 0, false, 'Rate not supplied'],
            ],
            'UAE' => [
                ['UAE Gulf Region', 'UAE-GULF', 10, 0, 0, true, 'Gulf region; delivery time not supplied'],
                ['UAE International', 'UAE-INTERNATIONAL', 30, 0, 0, true, 'International; delivery time not supplied'],
                ['UAE Domestic', 'UAE-DOMESTIC', 0, 0, 0, false, 'Rate not supplied'],
            ],
        ];
        $global = DB::table('marketplaces')->whereRaw('LOWER(code) = ?', ['global'])->first();
        $marketplaces['GLOBAL'] = $global;
        foreach ($definitions as $code => $zones) {
            if (empty($marketplaces[$code])) {
                continue;
            }
            foreach ($zones as [$name, $zoneCode, $baseFee, $minimumDays, $maximumDays, $isActive, $coverage]) {
                $exists = DB::table('delivery_zones')->where('code', $zoneCode)->exists();
                DB::table('delivery_zones')->updateOrInsert(['code' => $zoneCode], [
                    'marketplace_id' => $marketplaces[$code]->id,
                    'country_id' => $code === 'GLOBAL' ? null : $marketplaces[$code]->country_id,
                    'name' => $name,
                    'base_fee' => $baseFee,
                    'per_km_fee' => 0,
                    'estimated_days_min' => $minimumDays,
                    'estimated_days_max' => $maximumDays,
                    'is_active' => $isActive,
                    'rules' => json_encode(['status' => $isActive ? 'configured' : 'draft', 'coverage' => $coverage, 'currency' => $this->marketplaceCurrency($marketplaces[$code]->id), 'source_notes' => 'Operator-supplied delivery configuration.', 'delivery_time_confirmed' => $minimumDays > 0 && $maximumDays > 0]),
                    'updated_at' => now(), 'created_at' => now(),
                ]);
                $exists ? $stats['updated']++ : $stats['created']++;
            }
        }

        return $stats;
    }

    private function marketplaceCurrency(int $marketplaceId): ?string
    {
        return DB::table('marketplaces as m')->leftJoin('currencies as c', 'c.id', '=', 'm.currency_id')->where('m.id', $marketplaceId)->value('c.code');
    }

    /** @return array{price:float,quantity:int}|null */
    private function lowestPriceBreak(mixed $raw): ?array
    {
        $breaks = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($breaks)) {
            return null;
        }
        $selected = null;
        foreach ($breaks as $break) {
            $quantity = (int) ($break['qFrom'] ?? 0);
            if ($quantity < 1 || ! isset($break['price']) || ! is_numeric($break['price']) || (float) $break['price'] <= 0) {
                continue;
            }
            if ($selected === null || $quantity < $selected['quantity']) {
                $selected = ['price' => (float) $break['price'], 'quantity' => $quantity];
            }
        }

        return $selected;
    }

    private function withLock(callable $callback): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $callback();
            return;
        }
        $locked = (bool) (DB::selectOne('SELECT pg_try_advisory_lock(?) AS locked', [self::LOCK_KEY])->locked ?? false);
        if (! $locked) {
            throw new \RuntimeException('Another catalog seeding operation is already running.');
        }
        try {
            $callback();
        } finally {
            DB::select('SELECT pg_advisory_unlock(?)', [self::LOCK_KEY]);
        }
    }
}
