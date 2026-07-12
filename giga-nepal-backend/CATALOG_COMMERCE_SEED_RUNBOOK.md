# Catalog Commerce Seed Runbook

## Source and price rule

The command reads `catalog_distributor_offers` from the active `jlcpcb_parts_database` catalog source. It uses the lowest valid USD quantity break as the source cost and creates a Global marketplace price at `source cost x 1.05`. The minimum source quantity is retained in the pricing rule.

The values are source-backed LCSC/JLCPCB distributor prices, not manufacturer price claims. The source URL, offer ID, fetch time, source unit price, pricing rule, and source review status are retained on every generated marketplace price. Pending source review status is never promoted by this command.

## Inventory and warehouses

- Shenzhen China: Global marketplace, 10 operator-directed units for every catalog product.
- Kathmandu Nepal: Nepal marketplace, 2 units for the first 500 catalog products.
- New Delhi India: India marketplace, 2 units for the first 500 catalog products.
- Dubai UAE: Global marketplace, 2 units for the first 500 catalog products.

Regional sample quantities are explicit operator-directed availability, not supplier stock feeds. They are stored in stock metadata with provenance fields.

## Delivery zones

- Global Economy: USD 5, 10-15 days, active.
- Global Express: USD 15, 3-7 days, active.
- India Express: INR 100, 2-4 days, active.
- Nepal nationwide: NPR 150, delivery time not supplied.
- UAE Gulf Region: AED 10, delivery time not supplied.
- UAE International: AED 30, delivery time not supplied.

Zones without supplied freight rates remain inactive drafts. No tax zones are created by this command, so checkout readiness remains blocked until taxes are configured.

## Execution

```bash
php artisan catalog:seed-global-commerce --dry-run
php artisan catalog:seed-global-commerce --apply --regional-sample-size=500 --regional-sample-quantity=2
```

Run the dry run first. The command holds a PostgreSQL advisory lock, updates existing seeded rows idempotently, and does not delete catalog, price, or inventory records.
