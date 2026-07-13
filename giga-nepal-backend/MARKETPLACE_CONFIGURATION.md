# Marketplace Configuration

Marketplace configuration is database-backed through `marketplaces`, countries,
currencies, domains and settings. `MarketplaceContextResolver` is the central
resolution entry point; domain resolution is handled by
`DomainMarketplaceResolver`, followed by configured code/session preference and
the global fallback. Controllers and templates must not branch on hostnames.

The admin configuration screen supports General, Domain & Routing, Status &
Access, SEO, Branding and Advanced tabs. It is backed by
`MarketplaceConfigController` and `GlobalMarketplaceContextService` rather than
hard-coded market names or domains.

Changes to a marketplace must be validated through the launch/readiness checks
before activation. Domain, currency, tax, payment, shipping, warehouse and
visibility configuration are operational dependencies and should not be implied
from the marketplace label.
