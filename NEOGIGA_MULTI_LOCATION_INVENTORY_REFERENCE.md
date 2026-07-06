# Multi-Location Inventory Reference

Best source: UltimatePOS.
Secondary sources: Smart POS SaaS, Radminly inventory UI, Salesy purchase orders.

## UltimatePOS Source

Root: `/tmp/neogiga-reference-rescan/ultimatepos/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1`

Useful files:
- `app/Product.php`
- `app/BusinessLocation.php`
- `app/PurchaseLine.php`
- `app/Transaction.php`
- `app/TransactionPayment.php`
- `app/Utils/ProductUtil.php`
- `app/Utils/TransactionUtil.php`
- `app/Utils/CashRegisterUtil.php`
- `app/Http/Controllers/StockAdjustmentController.php`
- `app/Http/Controllers/PurchaseController.php`
- `app/Http/Controllers/OpeningStockController.php`
- `app/Http/Controllers/ImportOpeningStockController.php`
- `app/Http/Controllers/SellPosController.php`
- `database/migrations/2017_11_20_063603_create_transaction_sell_lines.php`
- `database/migrations/2018_01_31_125836_create_cash_register_transactions_table.php`
- `database/migrations/2021_03_03_162021_add_purchase_order_columns_to_purchase_lines_and_transactions_table.php`
- `database/migrations/2018_07_24_160319_add_lot_no_line_id_to_transaction_sell_lines_table.php`

## Smart POS SaaS Source

Root: `/tmp/neogiga-reference-rescan/smartpos-221/dist/pos-saas`

Useful for multistore SaaS POS flow, product lookup, sales screen, and stock movement comparison.

## Salesy Source

Root: `/tmp/neogiga-reference-rescan/salesysaas-79/codecanyon-30241292-salesy-saas-business-sales-crm/main-file`

Useful files:
- `database/migrations/2025_01_31_000010_create_purchase_orders_table.php`
- `database/migrations/2025_01_31_000011_create_purchase_order_products_table.php`
- `app/Models/PurchaseOrder.php`
- `app/Http/Controllers/PurchaseOrderController.php`

## Recommended NeoGiga Schema Refinement

NeoGiga already has warehouses, inventory stocks, movements, reserved stock, POS shell tables, and purchase-order foundation. Refine incrementally:
- `inventory_stocks`: product, variant, warehouse, vendor, marketplace, country, region, city, available, reserved, damaged, incoming, on_hand, reorder point.
- `inventory_movements`: append-only movement ledger with before/after quantities, movement type, reference type/id, idempotency key, cost, source metadata.
- `stock_transfers`: transfer header with source/destination warehouse, status, shipped/received timestamps.
- `stock_transfer_items`: product/variant quantity and received quantity.
- `suppliers` and purchase-order tables with receiving into movements.
- Barcode/QR aliases per product/variant and warehouse lookup.

## Adapt

- UltimatePOS transaction and product utility concepts.
- Purchase receiving and stock-adjustment workflows.
- Opening stock import validation.
- Cash register and POS sale stock deduction ideas.

## Rewrite

- All code and migrations must be rewritten for NeoGiga Laravel 11/PostgreSQL.
- Avoid UltimatePOS business/account tables as-is; map to NeoGiga marketplace/vendor/warehouse model.

