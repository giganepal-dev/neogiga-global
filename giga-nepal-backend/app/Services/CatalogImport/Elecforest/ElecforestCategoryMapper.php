<?php

namespace App\Services\CatalogImport\Elecforest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ElecforestCategoryMapper
{
    private const SUBCATEGORY_SLUGS = [
        'Capacitors' => 'capacitors', 'Resistor' => 'resistors', 'Inductor' => 'inductors',
        'Crystal Oscillator' => 'crystals-oscillators', 'Display/LED' => 'displays',
        'Switch' => 'switches-relays', 'Relay' => 'switches-relays', 'Wires' => 'wires-cables',
        'Temperature/Humidity' => 'temperature-humidity', 'Thermistor' => 'temperature-humidity',
        'Infrared/Distance' => 'proximity-distance', 'Laser/Pressure' => 'pressure-force',
        'Gas/Touch' => 'gas-environmental', 'Power' => 'power-modules',
        'Wireless' => 'wifi-modules', 'Bluetooth' => 'bluetooth-ble', 'GSM/GPS' => 'gnssgps',
        'Boards' => 'development-boards', 'Driver Board' => 'motor-drivers',
        'Motor' => 'dc-gear-motors', 'Stepper Motor' => 'stepper-motors', 'Wheel' => 'wheels-tracks',
        'Gear' => 'gears-racks', 'Cooling Fan' => 'maker-accessories', 'Shell/Bracket/Box' => 'maker-accessories',
        'Nozzle' => 'printer-parts-upgrades', 'Head Extruder' => 'printer-parts-upgrades',
        'Heating Block' => 'printer-parts-upgrades', 'Timing Belt' => 'belts-pulleys',
        'Antenna' => 'antennas', 'Pin Header' => 'connectors', 'PCB' => 'breadboards-prototyping',
        'IC' => 'semiconductors', 'Diode' => 'discrete-semiconductors', 'Potentiometer' => 'resistors',
        'Buzzer' => 'optoelectronics', 'Propeller' => 'propellers',
    ];

    private const MAIN_SLUGS = [
        'Electronic Components' => 'electronic-components', 'Sensors' => 'sensors',
        'Modules' => 'embedded-modules', 'Accessories' => 'maker-accessories',
        '3D Printer' => 'printer-parts-upgrades', 'Raspberry PI' => 'single-board-computers',
        'Kits' => 'educational-kits', 'Tools' => 'diy-maker-tools',
    ];

    /** @return array{category_id:?int,category_name:string,path:list<string>,confidence:float,status:string,source_key:string} */
    public function resolve(string $main, string $subcategory, int $sourceId, bool $persist = true): array
    {
        $sourceKey = hash('sha256', mb_strtolower(trim($main).'|'.trim($subcategory)));
        $approved = $sourceId > 0
            ? DB::table('supplier_category_mappings')->where('catalog_source_id', $sourceId)->where('source_category_key', $sourceKey)->whereNotNull('category_id')->first()
            : null;
        if ($approved) {
            $category = DB::table('product_categories')->find($approved->category_id);
            if ($category) {
                return [
                    'category_id' => (int) $category->id, 'category_name' => $category->name,
                    'path' => $this->path((int) $category->id), 'confidence' => (float) $approved->confidence,
                    'status' => (string) $approved->mapping_status, 'source_key' => $sourceKey,
                ];
            }
        }

        $subcategorySlug = $this->lookupSlug(self::SUBCATEGORY_SLUGS, $subcategory);
        $mainSlug = $this->lookupSlug(self::MAIN_SLUGS, $main);
        $slug = $subcategorySlug ?? $mainSlug;
        $resolved = $slug !== null;
        $confidence = $subcategorySlug !== null ? 0.95 : ($resolved ? 0.85 : 0.2);
        $category = $slug ? DB::table('product_categories')->where('slug', $slug)->first() : null;

        if (! $category && $persist && ! $resolved) {
            $category = $this->reviewCategory();
        }

        $path = $category ? $this->path((int) $category->id) : ($resolved ? [$slug] : config('elecforest_import.review_category_path'));
        $result = [
            'category_id' => $category ? (int) $category->id : null,
            'category_name' => $category->name ?? ($resolved ? Str::headline((string) $slug) : 'ElecForest Review'),
            'path' => $path,
            'confidence' => $confidence,
            'status' => $resolved && $category ? 'auto_mapped' : 'pending_review',
            'source_key' => $sourceKey,
        ];

        if ($persist) {
            DB::table('supplier_category_mappings')->updateOrInsert(
                ['catalog_source_id' => $sourceId, 'source_category_key' => $result['source_key']],
                [
                    'source_category_name' => $subcategory !== '' ? $subcategory : $main,
                    'source_category_path' => trim($main.' / '.$subcategory, ' /'),
                    'category_id' => $result['category_id'],
                    'confidence' => $confidence,
                    'mapping_status' => $result['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return $result;
    }

    private function reviewCategory(): object
    {
        $root = DB::table('product_categories')->where('slug', 'catalog-imports')->first();
        if (! $root) {
            $id = DB::table('product_categories')->insertGetId([
                'name' => 'Catalog Imports', 'slug' => 'catalog-imports', 'description' => 'Internal review categories for imported catalog records.',
                'is_active' => false, 'is_featured' => false, 'sort_order' => 999, 'seo_meta' => json_encode(['robots' => 'noindex,nofollow']),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $root = DB::table('product_categories')->find($id);
        }

        $child = DB::table('product_categories')->where('slug', 'elecforest-review')->first();
        if (! $child) {
            $id = DB::table('product_categories')->insertGetId([
                'parent_id' => $root->id, 'name' => 'ElecForest Review', 'slug' => 'elecforest-review',
                'description' => 'ElecForest records awaiting taxonomy and publication review.', 'is_active' => false,
                'is_featured' => false, 'sort_order' => 999, 'seo_meta' => json_encode(['robots' => 'noindex,nofollow']),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $child = DB::table('product_categories')->find($id);
        }

        return $child;
    }

    /** @return list<string> */
    private function path(int $categoryId): array
    {
        $path = [];
        $guard = 0;
        while ($categoryId > 0 && $guard++ < 10) {
            $category = DB::table('product_categories')->find($categoryId);
            if (! $category) {
                break;
            }
            array_unshift($path, (string) $category->name);
            $categoryId = (int) ($category->parent_id ?? 0);
        }

        return $path;
    }

    /** @param array<string, string> $map */
    private function lookupSlug(array $map, string $value): ?string
    {
        $needle = mb_strtolower(trim($value));
        $singularNeedle = mb_strtolower(Str::singular($needle));
        foreach ($map as $source => $slug) {
            $candidate = mb_strtolower(trim($source));
            if ($candidate === $needle || mb_strtolower(Str::singular($candidate)) === $singularNeedle) {
                return $slug;
            }
        }

        return null;
    }
}
