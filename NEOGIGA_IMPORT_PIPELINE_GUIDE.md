# NeoGiga Priority Supplier Import Pipeline

## Overview

This guide documents the unified Laravel structure for importing products from priority suppliers into the NeoGiga platform. The system supports a catalog of 25,000–30,000+ products from tiered suppliers.

## Supplier Tiers

### Tier 1 (Core Maker Ecosystem)
- **Adafruit** – Arduino, ESP32, Raspberry Pi, Feather, STEM products (~4,000+)
- **Waveshare** – Displays, e-paper, HATs, robotics, industrial modules (~8,000+)
- **OKYSTAR** – Arduino, sensors, modules, robotics (~1,500+)
- **SparkFun** – Sensors, development boards, education (~2,000+)
- **Seeed Studio** – Grove ecosystem, IoT, AI hardware (~6,000+)
- **DFRobot** – Robotics, education, industrial sensors (~3,000+)

### Tier 2 (Industrial & Electronics)
Arduino, Raspberry Pi, Espressif, STMicroelectronics, Texas Instruments, Analog Devices, Microchip, Nordic Semiconductor, NXP, Quectel, SIMCom, u-blox

### Tier 3 (Components & Connectors)
Mean Well, Hi-Link, JST, Molex, Omron, Panasonic, Murata, Vishay, Infineon, ROHM

## Database Schema

### New Tables Created

1. **suppliers** - Supplier master data
   - name, slug, tier (tier_1/tier_2/tier_3)
   - website_url, api_endpoint, api_credentials
   - logo_path, country, is_active, is_featured

2. **product_suppliers** - Product-to-supplier relationships
   - product_id, supplier_id
   - supplier_product_id, supplier_sku
   - mpn, upc_ean
   - cost_price, currency, lead_time_days
   - min_order_quantity, is_primary

3. **product_country_prices** - Country-wise pricing
   - product_id, country_id
   - base_price, sale_price, bulk_price
   - bulk_min_quantity, currency
   - is_available, price_valid_from/until

4. **product_warehouses** - Multi-warehouse inventory
   - product_id, warehouse_id
   - quantity, reserved_quantity, incoming_quantity
   - bin_location, last_restocked_at

5. **product_ai_features** - NeoGiga AI features
   - ai_summary, ai_bom_suggestions
   - ai_compatible_alternatives, ai_cross_sell_recommendations
   - ai_project_ideas
   - ai_pinout_diagrams, ai_wiring_examples
   - ai_engineering_notes, ai_datasheet_qa
   - ai_model_version, is_verified

6. **product_resources** - Product resources
   - type: datasheet, manual, cad_3d_model
   - arduino_library, platformio_library, circuitpython_library
   - example_code, github_example
   - documentation_link, video_tutorial
   - pinout_diagram, wiring_diagram

7. **import_jobs** - Import job tracking
   - job_type, status (pending/running/completed/failed)
   - total_items, processed_items
   - created_items, updated_items, failed_items
   - started_at, completed_at

## Product Data Structure

Each product includes:

- **Manufacturer** → brand_id (ProductBrand)
- **Supplier** → product_suppliers relationship
- **Brand** → ProductBrand model
- **MPN** → mpn field + normalized index
- **SKU** → sku field (unique)
- **UPC/EAN** → product_suppliers.upc_ean
- **Category & subcategory** → category_id (ProductCategory)
- **Technical specifications** → product_specifications, spec_template_fields
- **Features** → attributes (JSON)
- **Applications** → metadata['applications']
- **Compatible boards** → product_compatibility relationship
- **Related products** → product_related_items relationship
- **Accessories** → metadata['accessories']
- **Images** → product_images relationship
- **Datasheets** → product_resources (type: datasheet)
- **CAD/3D models** → product_resources (type: cad_3d_model)
- **Libraries** → product_resources (arduino/platformio/circuitpython_library)
- **GitHub examples** → product_resources (type: github_example)
- **Documentation links** → product_resources (type: documentation_link)
- **Country-wise pricing** → product_country_prices
- **Multi-warehouse inventory** → product_warehouses
- **SEO fields** → seo_meta (JSON)
- **AI-generated summary** → product_ai_features.ai_summary

## NeoGiga AI Features

For every product, enable:

1. **AI product summary** → ai_summary
2. **AI BOM suggestions** → ai_bom_suggestions
3. **Compatible alternatives** → ai_compatible_alternatives
4. **Cross-sell recommendations** → ai_cross_sell_recommendations
5. **Project ideas** → ai_project_ideas
6. **Engineering assistant** → ai_engineering_notes
7. **Pinout diagrams** → ai_pinout_diagrams
8. **Wiring examples** → ai_wiring_examples
9. **Sample code downloads** → product_resources (example_code)
10. **Datasheet Q&A** → ai_datasheet_qa

## Import Commands

Create artisan commands for each supplier:

```bash
php artisan neogiga:import:adafruit
php artisan neogiga:import:waveshare
php artisan neogiga:import:okystar
php artisan neogiga:import:sparkfun
php artisan neogiga:import:seeed
php artisan neogiga:import:dfrobot
```

## Importer Architecture

### BaseImporter Service

Located at: `app/Services/Importers/BaseImporter.php`

Each importer should extend this base class and implement:

```php
abstract public function getSupplierSlug(): string;
abstract public function fetchCategories(): array;
abstract public function fetchProducts(array $options = []): \Generator;
abstract public function normalizeProduct(array $rawProduct): array;
abstract protected function getSupplierName(): string;
abstract protected function getSupplierTier(): string;
abstract protected function getSupplierDescription(): ?string;
abstract protected function getSupplierWebsite(): ?string;
abstract protected function getSupplierCountry(): ?string;
```

### Import Process

Each importer should:

1. ✅ Import categories and brands
2. ✅ Import products and variants
3. ✅ Download images, datasheets, and manuals
4. ✅ Normalize specifications into searchable fields
5. ✅ Detect duplicate MPNs across suppliers
6. ✅ Generate SEO metadata
7. ✅ Keep products as draft until reviewed
8. ✅ Track updates from the original supplier

## Models Created

### Supplier Models
- `App\Models\Supplier\Supplier`
- `App\Models\Supplier\ProductSupplier`

### Marketplace Extensions
- `App\Models\Marketplace\ProductCountryPrice`
- `App\Models\Marketplace\ProductWarehouse`
- `App\Models\Marketplace\ProductAiFeature`
- `App\Models\Marketplace\ProductResource`
- `App\Models\Marketplace\ImportJob`

## Duplicate Detection

The system detects duplicates by:

1. **MPN matching** - Checks if MPN exists with different supplier
2. **SKU matching** - Checks global SKU uniqueness
3. **Cross-supplier linking** - Links same product from multiple suppliers

## Migration Files

All migrations are timestamped `2026_07_12_*` for easy identification:

1. `2026_07_12_000001_create_suppliers_table.php`
2. `2026_07_12_000002_create_product_suppliers_table.php`
3. `2026_07_12_000003_create_product_country_prices_table.php`
4. `2026_07_12_000004_create_product_warehouses_table.php`
5. `2026_07_12_000005_create_product_ai_features_table.php`
6. `2026_07_12_000006_create_product_resources_table.php`
7. `2026_07_12_000007_create_import_jobs_table.php`

## Running Migrations

```bash
cd /workspace/giga-nepal-backend
php artisan migrate
```

## Example Importer Implementation

```php
namespace App\Services\Importers;

class AdafruitImporter extends BaseImporter
{
    public function getSupplierSlug(): string { return 'adafruit'; }
    
    protected function getSupplierName(): string { return 'Adafruit'; }
    protected function getSupplierTier(): string { return 'tier_1'; }
    protected function getSupplierDescription(): ?string { 
        return 'Arduino, ESP32, Raspberry Pi, Feather, STEM products'; 
    }
    protected function getSupplierWebsite(): ?string { 
        return 'https://www.adafruit.com'; 
    }
    protected function getSupplierCountry(): ?string { 
        return 'USA'; 
    }
    
    public function fetchCategories(): array
    {
        // Fetch from Adafruit API or scrape
        return [
            ['name' => 'Arduino', 'parent_id' => null],
            ['name' => 'Raspberry Pi', 'parent_id' => null],
            // ...
        ];
    }
    
    public function fetchProducts(array $options = []): \Generator
    {
        // Yield products one at a time for memory efficiency
        foreach ($this->apiClient->getProducts() as $rawProduct) {
            yield $rawProduct;
        }
    }
    
    public function normalizeProduct(array $rawProduct): array
    {
        return [
            'name' => $rawProduct['name'],
            'sku' => $rawProduct['sku'],
            'mpn' => $rawProduct['mpn'] ?? null,
            'brand' => 'Adafruit',
            'category' => $rawProduct['category'],
            'description' => $rawProduct['description'],
            'short_description' => Str::limit($rawProduct['description'], 200),
            'supplier_sku' => $rawProduct['sku'],
            'cost_price' => $rawProduct['price'],
            'currency' => 'USD',
            'images' => $rawProduct['images'],
            'resources' => [
                [
                    'type' => 'datasheet',
                    'title' => 'Datasheet',
                    'external_url' => $rawProduct['datasheet_url'],
                ],
                [
                    'type' => 'github_example',
                    'title' => 'Example Code',
                    'github_repo' => $rawProduct['github_repo'],
                ],
            ],
            'attributes' => $rawProduct['specs'],
        ];
    }
}
```

## Artisan Command Example

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Marketplace\ImportJob;
use App\Services\Importers\AdafruitImporter;

class ImportAdafruitCommand extends Command
{
    protected $signature = 'neogiga:import:adafruit {--limit=}';
    protected $description = 'Import products from Adafruit';

    public function handle(): int
    {
        $job = ImportJob::create([
            'job_type' => 'adafruit',
            'status' => 'pending',
            'options' => ['limit' => $this->option('limit')],
        ]);

        $importer = new AdafruitImporter();
        $importer->run($job);

        $this->info("Import completed: {$job->created_items} created, {$job->updated_items} updated, {$job->failed_items} failed");

        return self::SUCCESS;
    }
}
```

## Next Steps

1. Create specific importer classes for each Tier 1 supplier
2. Implement API clients for each supplier
3. Create artisan commands for each importer
4. Set up scheduled jobs for periodic sync
5. Build admin UI for import job monitoring
6. Implement AI feature generation pipeline
7. Create review workflow for draft products

## File Locations

- **Migrations**: `/workspace/giga-nepal-backend/database/migrations/2026_07_12_*`
- **Models**: `/workspace/giga-nepal-backend/app/Models/Supplier/` and `/workspace/giga-nepal-backend/app/Models/Marketplace/`
- **Services**: `/workspace/giga-nepal-backend/app/Services/Importers/`
- **Commands**: `/workspace/giga-nepal-backend/app/Console/Commands/` (to be created)

## Estimated Catalog Size

| Supplier | Approx. Products |
|----------|-----------------|
| Adafruit | 4,000+ |
| Waveshare | 8,000+ |
| OKYSTAR | 1,500+ |
| SparkFun | 2,000+ |
| Seeed Studio | 6,000+ |
| DFRobot | 3,000+ |
| Arduino | 500+ |
| Raspberry Pi | 500+ |
| **Total** | **25,000–30,000+** |
