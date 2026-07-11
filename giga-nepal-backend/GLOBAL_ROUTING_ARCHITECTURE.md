# Global Routing Architecture

## Current State

Legacy URLs remain active. Locale-prefixed route aliases are now available behind `NEOGIGA_LOCALE_PREFIX_ROUTES`.

## Prefix Examples

- `/en/products`
- `/in/products`
- `/np/products`
- `/bd/products`
- `/de/products`
- `/br/products`

## Geo Routing Policy

Geo routing must be recommendation-first. Do not trap users. Manual country choice is remembered with the existing marketplace preference cookie.

## Feature Flags

- `NEOGIGA_LOCALE_PREFIX_ROUTES`
- `NEOGIGA_GEO_RECOMMENDATION_REDIRECT`
- `NEOGIGA_LOCALIZED_SITEMAPS`
- `NEOGIGA_LOCALIZED_PRICING`

## Next Implementation

Add middleware that recommends `/in` or `/np` from GeoIP/browser language/timezone only when the user has no stored preference.

