# Supplier Source Compliance Report

## Current State

| Supplier | Domains | Import | Media | Description reuse | Status |
| --- | --- | --- | --- | --- | --- |
| Adafruit | `adafruit.com` | Disabled | Disabled | Unknown | Pending manual policy review |
| Waveshare | `waveshare.com`, `waveshare.net` | Disabled | Disabled | Unknown | Pending manual policy review |
| OKYSTAR | `okystar.com` | Disabled | Disabled | Unknown | Pending manual policy review |

No supplier catalogue was fetched, imported, published, or deployed by this implementation.

## Required Approval Evidence

Before enabling a supplier, an administrator must record: permitted product URL/feed scope, robots result, terms review date, redistribution permission for factual data, description reuse permission, image/document rights, allowed request rate, and a contact address accepted by the supplier.

`catalog:supplier-audit` only records a robots observation and leaves `import_enabled=false`. It never treats a successful robots response as permission to reuse or publish supplier content.

## Prohibited Behaviour

- No authentication, bot-protection, CAPTCHA, rate-limit, or paywall bypass.
- No crawling after a policy block, HTTP 403, or HTTP 429.
- No supplier price/stock writes to marketplace pricing or inventory.
- No imported product publication, SEO indexing, or media download without explicit approval.
