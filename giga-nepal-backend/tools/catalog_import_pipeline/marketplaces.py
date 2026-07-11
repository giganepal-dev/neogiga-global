from __future__ import annotations

from pathlib import Path

import yaml


def load_marketplaces(path: Path | str) -> dict[str, dict[str, str]]:
    data = yaml.safe_load(Path(path).read_text(encoding="utf-8")) or {}
    return {str(code): dict(payload or {}) for code, payload in (data.get("marketplaces") or {}).items()}


def localized_overlays(product_name: str, mpn: str, category: str, marketplaces: dict[str, dict[str, str]]) -> dict[str, dict[str, str]]:
    overlays: dict[str, dict[str, str]] = {}
    for code, market in marketplaces.items():
        brand = market.get("brand") or "NeoGiga"
        country = market.get("country") or code.upper()
        currency = market.get("currency") or "USD"
        domain = market.get("domain") or "neogiga.com"
        slug = market.get("slug_template", "{mpn}-{category}").format(mpn=mpn, category=category, country=country)
        slug = "-".join(slug.casefold().replace("/", " ").split())
        title = market.get("product_title_template", "Buy {mpn} Online in {country} | NeoGiga").format(mpn=mpn, country=country, brand=brand, category=category)
        description = market.get("product_description_template", "Source {name} through {brand}. Availability, tax and shipping are confirmed during quote/order review.").format(name=product_name, mpn=mpn, country=country, brand=brand, category=category)
        overlays[code] = {
            "locale": market.get("locale", "en"),
            "country": country,
            "currency": currency,
            "domain": domain,
            "slug": slug,
            "url": f"https://{domain}/products/{slug}",
            "seo_title": title[:90],
            "meta_description": description[:158],
            "h1": market.get("h1_template", "{mpn} in {country}").format(mpn=mpn, country=country, brand=brand, category=category),
            "breadcrumb": f"{country} / {category} / {mpn}",
            "canonical": f"https://{domain}/products/{slug}",
            "hreflang": market.get("locale", "en"),
            "shipping_text": market.get("shipping_text", "Shipping availability is confirmed during quote/order review."),
            "payment_methods": market.get("payment_methods", "Configured marketplace payment methods"),
            "tax_information": market.get("tax_information", "Taxes are calculated according to marketplace configuration."),
            "warranty_information": market.get("warranty_information", "Warranty is confirmed by seller/manufacturer terms."),
            "faq": market.get("faq", "Availability, compliance and lead time are verified during order review."),
            "og_title": title[:90],
            "og_description": description[:158],
            "twitter_title": title[:90],
            "twitter_description": description[:158],
        }
    return overlays

