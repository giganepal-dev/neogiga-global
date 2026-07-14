# NeoGiga Design Regression Audit

Date: 2026-07-14

## Safety scope

- Repository: `giganepal-dev/neogiga-global`
- Safety branch: `fix/restore-design-brand-images-seo`
- Starting commit: `0909d044699ef6d50fa52faa15ffc4cdba98b7b4`
- Pre-task tracked patch: `.codex-backups/20260714-design-brand-images-seo/pre-task-tracked.patch`
- Existing database, routes, modules, migrations, data, and the dark "Precision Engineering" theme are upgrade-only inputs and must be preserved.

## Selected approved design baseline

- Platform layout and catalog design: `e1e14fa` — **Harden public frontend layout theme**.
- Homepage design: `bae072e` — **Redesign home: elevate to professional global-marketplace framing**.
- The relevant layout/home files at current `HEAD` still descend from these commits. A wholesale checkout is neither required nor safe.
- The newer `pcb-usable-portal` line contains useful product-gallery/mobile and brand-page integration patterns, but also deletes existing catalog/import code. Only compatible presentation and integration ideas may be adapted.

## Regression findings

1. The approved header, footer, colors, spacing, navigation, homepage, product listing, and category presentation remain present.
2. The public product-detail template still renders a branded placeholder instead of its loaded `product_images` relation. This is an integration regression inside the approved layout.
3. The local public storage link is absent; URL generation must work with configured storage disks and production release symlinks.
4. `/en/brands` deliberately redirects to categories, so there is no brand directory despite an existing brand API and brand detail route.
5. `/en/brand/{slug}` uses the generic SEO landing template rather than a dedicated page in the approved design.
6. Current working-tree catalog changes add structured data, localized links, logo placeholders, and brand/manufacturer links. These changes are newer than `HEAD` and must not be discarded.

## Restore decision

Preserve the approved baseline and current data-aware additions. Adapt brand directory/detail and media gallery functionality into the existing components. Do not restore complete files from another commit or branch.

## Verified result

The selective integration is complete. The existing header, footer, navigation, typography, colors, spacing and responsive structure remain in place. Product media/gallery and brand pages were added through the existing design classes. Browser regression found and corrected only two scoped defects: inherited white detail panels on the dark product page and mobile overflow from the landing country/language controls. Desktop and mobile production checks report no horizontal page overflow, broken images or console errors.
