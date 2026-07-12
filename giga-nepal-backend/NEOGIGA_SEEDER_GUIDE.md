# NeoGiga Supplier & Product Seeder - Quick Start Guide

## Overview
This guide explains how to seed the NeoGiga database with priority suppliers and sample products **without requiring API access**. All data is pre-configured and ready to use.

## What Gets Seeded

### Suppliers (27 Total)
**Tier 1 - Core Maker Ecosystem (6):**
- Adafruit, Waveshare, OKYSTAR, SparkFun, Seeed Studio, DFRobot

**Tier 2 - Industrial & Electronics (12):**
- Arduino, Raspberry Pi, Espressif, STMicroelectronics, Texas Instruments, Analog Devices, Microchip, Nordic Semiconductor, NXP, Quectel, SIMCom, u-blox

**Tier 3 - Components & Connectors (9):**
- Mean Well, Hi-Link, JST, Molex, Omron, Panasonic, Murata, Vishay, Infineon, ROHM

### Sample Products (10 from 5 suppliers)
Each product includes:
- Complete specifications (JSON)
- Features and applications
- Compatible boards
- Multi-currency pricing (USD, EUR, GBP, INR)
- Bulk pricing tiers
- Warehouse inventory
- AI feature placeholders
- Resource links (datasheets, GitHub, libraries)

## Usage

### Option 1: Seed Everything (Recommended for Development)
```bash
php artisan neogiga:seed:suppliers --all
```

### Option 2: Seed Only Suppliers
```bash
php artisan neogiga:seed:suppliers --suppliers
```

### Option 3: Seed Only Sample Products
```bash
php artisan neogiga:seed:suppliers --products
```

### Option 4: Fresh Start (Wipes Database!)
```bash
php artisan neogiga:seed:suppliers --fresh --all
```
⚠️ **Warning:** This will delete all existing data!

### Option 5: Default (No Arguments)
```bash
php artisan neogiga:seed:suppliers
```
Same as `--all`

## Sample Products Included

| Supplier | Product | Price (USD) | Category |
|----------|---------|-------------|----------|
| Adafruit | Feather M4 Express | $19.95 | Development Boards |
| Adafruit | HUZZAH32 ESP32 | $16.95 | Development Boards |
| Waveshare | 7" HDMI LCD Touch | $69.99 | Displays |
| Waveshare | 2.13" e-Paper Display | $24.99 | Displays |
| SparkFun | Pro Micro ATmega32U4 | $19.95 | Development Boards |
| SparkFun | Qwiic Shield for Uno | $9.95 | Shields & Add-ons |
| Seeed Studio | XIAO ESP32C3 | $9.90 | Development Boards |
| Seeed Studio | Grove Temp Sensor DS18B20 | $8.90 | Sensors |
| DFRobot | FireBeetle 2 ESP32-E | $14.90 | Development Boards |
| DFRobot | Gravity Ultrasonic Sensor | $12.90 | Sensors |

## Data Structure

### Each Product Contains:
```php
[
    'name' => 'Product Name',
    'manufacturer' => 'Brand Name',
    'brand' => 'Brand Name',
    'mpn' => 'Manufacturer Part Number',
    'sku' => 'SKU',
    'upc_ean' => 'UPC/EAN or null',
    'category' => 'Main Category',
    'subcategory' => 'Sub Category',
    'description' => 'Full description',
    'specifications' => json_encode([...]),
    'features' => json_encode([...]),
    'applications' => json_encode([...]),
    'compatible_boards' => json_encode([...]),
    'price_usd' => 19.95,
    'stock_quantity' => 150,
    'images' => json_encode([...]),
    'datasheet_url' => 'https://...',
    'github_repo' => 'https://...',
    'arduino_library' => 'Library Name',
    'platformio_id' => 'board_id',
    'circuitpython_compatible' => true/false,
]
```

### Country Pricing (Auto-generated):
- 🇺🇸 USD - Base price
- 🇪🇺 EUR - 0.92x USD
- 🇬🇧 GBP - 0.79x USD
- 🇮🇳 INR - 83x USD

### Bulk Pricing Tiers:
- 1-9 units: 0% discount
- 10-49 units: 5% discount
- 50-99 units: 10% discount
- 100+ units: 15% discount

## Verification

After seeding, verify the data:

```bash
# Check suppliers count
php artisan tinker
>>> App\Models\Supplier\Supplier::count()
27

>>> App\Models\Supplier\Supplier::where('tier', 'tier_1')->count()
6

# Check products
>>> App\Models\Marketplace\Product::count()
10

# Check country prices
>>> App\Models\Marketplace\ProductCountryPrice::count()
40  # 10 products × 4 countries

# Check resources
>>> App\Models\Marketplace\ProductResource::count()
~25  # Varies by product
```

## Next Steps

### 1. Review in Admin Dashboard
Navigate to your admin panel to review seeded products (all in draft status).

### 2. Generate AI Features
Once AI services are configured:
```bash
php artisan neogiga:ai:generate --products=all
```

### 3. Publish Products
Change product status from `draft` to `active` when ready.

### 4. Run Full Imports
When API credentials are available:
```bash
php artisan neogiga:import:adafruit --products --pages=5
php artisan neogiga:import:waveshare --products
php artisan neogiga:import:sparkfun --products
php artisan neogiga:import:seeed --products
php artisan neogiga:import:dfrobot --products
```

## File Locations

### Seeders
- `/database/seeders/Suppliers/SupplierSeeder.php` - 27 suppliers
- `/database/seeders/Suppliers/SampleProductSeeder.php` - 10 sample products

### Command
- `/app/Console/Commands/Suppliers/SeedSuppliers.php`

### Models Used
- `App\Models\Supplier\Supplier`
- `App\Models\Supplier\ProductSupplier`
- `App\Models\Marketplace\Product`
- `App\Models\Marketplace\ProductCountryPrice`
- `App\Models\Marketplace\ProductWarehouse`
- `App\Models\Marketplace\ProductAiFeature`
- `App\Models\Marketplace\ProductResource`

## Troubleshooting

### "Class not found" errors
Run composer dump-autoload:
```bash
composer dump-autoload
```

### Foreign key constraint errors
Ensure migrations have run:
```bash
php artisan migrate:fresh --seed
```

### Products not showing
Check that products are in draft status:
```bash
php artisan tinker
>>> App\Models\Marketplace\Product::where('status', 'draft')->count()
```

## Notes

- ✅ **No API required** - All data is static and pre-configured
- ✅ **Safe for development** - Use `--fresh` only in dev environments
- ✅ **Draft by default** - Products won't appear on frontend until published
- ✅ **Idempotent** - Safe to run multiple times (uses updateOrCreate)
- ✅ **Production-ready structure** - Same schema as full importer pipeline

---

**Estimated Setup Time:** 2-5 minutes  
**Catalog Size After Seeding:** 27 suppliers, 10 products  
**Ready for:** Development, testing, UI prototyping, AI feature testing
