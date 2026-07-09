# NeoGiga Multi-Vendor Vendor System Guide

Vendors are global records. Marketplace participation is controlled by `vendor_marketplace_approvals`. Seller products remain protected by existing seller policies and are not public until vendor/product approval and visibility rules allow them.

Key tables: `vendors`, `vendor_profiles`, `vendor_marketplace_approvals`, `vendor_staff`, `vendor_roles`, `vendor_documents`, `vendor_products`, `vendor_audit_logs`.

Key APIs: `/api/v1/vendors/*`, `/api/v1/seller/*`, `/api/v1/admin/vendors/*`, `/api/v1/admin/vendor-approvals/*`.

Security: seller routes require `api.token` and seller permissions; admin routes require `admin.token`.
