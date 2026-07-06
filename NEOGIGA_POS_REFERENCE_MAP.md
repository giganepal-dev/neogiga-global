# NeoGiga POS Reference Map

Best POS source: UltimatePOS.
Secondary POS source: Smart POS SaaS.

## UltimatePOS Exact Files

Root: `/tmp/neogiga-reference-rescan/ultimatepos/codecanyon-21216332-ultimate-pos-stock-management-point-of-sale-application/UltimatePOS-CodeBase-V7.1`

Controllers/services:
- `app/Http/Controllers/SellPosController.php`
- `app/Http/Controllers/CashRegisterController.php`
- `app/Http/Controllers/PrinterController.php`
- `app/Http/Controllers/LabelsController.php`
- `app/Utils/CashRegisterUtil.php`
- `app/Utils/TransactionUtil.php`
- `app/Utils/ProductUtil.php`

Models/schema:
- `app/CashRegister.php`
- `app/CashDenomination.php`
- `app/Transaction.php`
- `app/TransactionPayment.php`
- `app/BusinessLocation.php`
- `app/Printer.php`
- `app/Barcode.php`
- `database/migrations/2018_01_31_125836_create_cash_register_transactions_table.php`
- `database/migrations/2021_04_15_063449_add_denominations_column_to_cash_registers_table.php`
- `database/migrations/2017_11_20_063603_create_transaction_sell_lines.php`
- `database/migrations/2018_07_25_172004_add_discount_columns_to_transaction_sell_lines_table.php`

Views:
- `resources/views/sale_pos/**`
- `resources/views/cash_register/**`
- `resources/views/printer/**`
- `resources/views/labels/**`

## Adaptation Plan

- Use NeoGiga `pos_sessions`, `pos_sales`, `pos_sale_items`, and `pos_payments` rather than UltimatePOS transaction tables.
- Add cash drawer movement and denomination tables incrementally.
- Add receipt templates and print settings under admin POS settings.
- Add barcode product lookup and quick-sale UI.
- Add return/refund flow that writes inventory reversal movements.

## Risk

UltimatePOS is commercial and large. Use as workflow reference only; rebuild code in NeoGiga style.

