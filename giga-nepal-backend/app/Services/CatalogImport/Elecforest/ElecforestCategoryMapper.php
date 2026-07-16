<?php

namespace App\Services\CatalogImport\Elecforest;

use App\Services\Catalog\CategoryResolutionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ElecforestCategoryMapper
{
    private const SUBCATEGORY_SLUGS = [
        'Capacitors' => 'capacitors', 'Resistor' => 'resistors', 'Inductor' => 'inductors',
        'Crystal Oscillator' => 'crystals-oscillators', 'Display/LED' => 'displays', 'Switch' => 'switches-relays',
        'Relay' => 'switches-relays', 'Wires' => 'wires-cables', 'Temperature/Humidity' => 'temperature-humidity',
        'Thermistor' => 'temperature-humidity', 'Infrared/Distance' => 'proximity-distance', 'Laser/Pressure' => 'pressure-force',
        'Gas/Touch' => 'gas-environmental', 'Power' => 'power-modules', 'Wireless' => 'wifi-modules',
        'Bluetooth' => 'bluetooth-ble', 'GSM/GPS' => 'gnssgps', 'Boards' => 'development-boards',
        'Driver Board' => 'motor-drivers', 'Motor' => 'dc-gear-motors', 'Stepper Motor' => 'stepper-motors',
        'Wheel' => 'wheels-tracks', 'Gear' => 'gears-racks', 'Cooling Fan' => 'maker-accessories',
        'Shell/Bracket/Box' => 'maker-accessories', 'Nozzle' => 'printer-parts-upgrades',
        'Head Extruder' => 'printer-parts-upgrades', 'Heating Block' => 'printer-parts-upgrades',
        'Timing Belt' => 'belts-pulleys', 'Antenna' => 'antennas', 'Pin Header' => 'connectors',
        'PCB' => 'breadboards-prototyping', 'IC' => 'semiconductors', 'Diode' => 'discrete-semiconductors',
        'Potentiometer' => 'resistors', 'Buzzer' => 'optoelectronics', 'Propeller' => 'propellers',
    ];

    private const MAIN_SLUGS = [
        'Electronic Components' => 'electronic-components', 'Sensors' => 'sensors', 'Modules' => 'embedded-modules',
        'Accessories' => 'maker-accessories', '3D Printer' => 'printer-parts-upgrades',
        'Raspberry PI' => 'single-board-computers', 'Kits' => 'educational-kits', 'Tools' => 'diy-maker-tools',
    ];

    public function __construct(private readonly CategoryResolutionService $resolver) {}

    /** @return array{category_id:?int,category_name:string,path:list<string>,confidence:float,status:string,source_key:string,requires_review:bool,matched_by:string,reasons:list<string>,parent_category_id:?int} */
    public function resolve(string $main, string $subcategory, int $sourceId, bool $persist = true): array
    {
        $sourceKey = hash('sha256', mb_strtolower(trim($main).'|'.trim($subcategory)));
        $candidate = $this->lookupSlug(self::SUBCATEGORY_SLUGS, $subcategory) ?? $this->lookupSlug(self::MAIN_SLUGS, $main) ?? $subcategory ?: $main;
        $resolved = $this->resolver->resolve($candidate, [
            'catalog_source_id' => $sourceId > 0 ? $sourceId : null,
            'source_name' => 'ElecForest',
            'source_key' => $sourceKey,
            'source_category_name' => $subcategory !== '' ? $subcategory : $main,
            'source_category_path' => trim($main.' / '.$subcategory, ' /'),
            'manufacturer_category' => $main,
        ]);

        if ($persist && Schema::hasTable('supplier_category_mappings')) {
            DB::table('supplier_category_mappings')->updateOrInsert(
                ['catalog_source_id' => $sourceId, 'source_category_key' => $sourceKey],
                [
                    'source_category_name' => $subcategory !== '' ? $subcategory : $main,
                    'source_category_path' => trim($main.' / '.$subcategory, ' /'),
                    'category_id' => $resolved['category_id'],
                    'confidence' => $resolved['confidence'],
                    'mapping_status' => $resolved['requires_review'] ? 'pending_review' : 'auto_mapped',
                    'created_at' => now(), 'updated_at' => now(),
                ],
            );
        }

        return array_merge($resolved, [
            'category_name' => $resolved['category_name'] ?? 'Pending category review',
            'status' => $resolved['requires_review'] ? 'pending_review' : 'auto_mapped',
        ]);
    }

    private function lookupSlug(array $map, string $value): ?string
    {
        $needle = mb_strtolower(trim($value));
        foreach ($map as $source => $slug) {
            if (mb_strtolower($source) === $needle || mb_strtolower(rtrim($source, 's')) === rtrim($needle, 's')) {
                return $slug;
            }
        }

        return null;
    }
}
