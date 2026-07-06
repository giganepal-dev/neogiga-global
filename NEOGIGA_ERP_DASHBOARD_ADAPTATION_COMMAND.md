# NeoGiga ERP Dashboard Adaptation Command

Implement ERP/dashboard reporting using UltimatePOS, Salesy, and Smartend as references only.

Build:
- Admin dashboard widgets: revenue, orders, inventory value, low stock, POS sales, vendor payouts, refunds, customer growth, marketing campaign performance.
- ERP reports: sales report, order report, vendor report, stock report, branch/warehouse report, purchase report, tax report, payment ledger report.
- Invoice document model and PDF/print export.
- Expense/tax/category settings.
- Export jobs with status, source filters, requested_by, completed_at, and downloadable file metadata.

Rules:
- Do not replace existing admin dashboard.
- Add pages incrementally.
- Do not copy reference code or assets.
- Use NeoGiga PostgreSQL-safe migrations.
- Update `CHANGELOG.md`.

