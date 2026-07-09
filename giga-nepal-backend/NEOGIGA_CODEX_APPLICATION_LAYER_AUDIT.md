# NeoGiga Codex Application Layer Audit

## What Exists

- 122 models, including marketplace, marketing, LMS, POS, affiliate, ERP, promotion, and legacy IoT models.
- 49 services, including inventory, POS, marketing, LMS, AI, affiliate, ERP, promotion.
- 51 controllers across public API, admin API, and server-rendered admin.
- 11 jobs, all marketing-related.

## Strengths

- Inventory and POS core mutations are in services (`StockMovementService`, `ReservationService`, `PosService`).
- LMS logic is separated into services.
- Marketing has dedicated services for segmentation, consent, campaigns, analytics, and templates.
- ERP, affiliate, and promotion layers now have service classes.

## Risks

- Several controllers remain stubs or placeholders: `MarketplaceAdminController`, `ProductAdminController`, `VendorAdminController`, `ImportExportController`, `AiCommerceController`.
- Marketing jobs are placeholder loggers for important workflows.
- No policies found in `app/Policies`; authorization mostly relies on middleware and custom permissions.
- `php artisan test` unavailable, so service/controller behavior is not continuously verifiable.
- AI/BOM controller does not use the AI services yet.
- Payment/wallet business logic is not mature enough for real money.
- POS refund and import/export flows are not implemented.

## Specific Review Items

- Keep high-risk logic in services with transactions: payment capture/refund, wallet entries, POS refund, stock transfer, checkout.
- Add form request classes or stricter request validation to admin controllers.
- Add authorization policies for admin/vendor/customer data access.
- Add route/model binding safeguards to avoid IDOR in admin/public APIs.
- Add service tests for inventory, POS, checkout, affiliate commission, coupon/gift-card redemption, and ERP purchase receiving.

