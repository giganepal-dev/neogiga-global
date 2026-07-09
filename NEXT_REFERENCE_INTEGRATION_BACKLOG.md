# NEXT_REFERENCE_INTEGRATION_BACKLOG (2026-07-09)

Ordered by value. Each item references its blueprint in REFERENCE_INTEGRATION_PLAN.md.

1. **Support chat foundation** (plan §4) — `conversations` + `conversation_messages` guarded
   additive migration; customer API (create/list/reply); admin inbox `/admin/chats` with staff
   assignment + close; placeholders for AI-assistant responder + human handoff. Covers the
   prompt's support-chat, seller-chat, product-Q&A-chat, transcript storage, admin chat mgmt,
   assignment, and handoff items. Blueprint: archive Chat/ConversationController.
2. **Product reviews** (plan §5) — `product_reviews` migration, admin moderation page, display
   block + rating aggregate on `/products/{slug}`. Blueprint: archive ReviewController.
3. **Real RFQ form on product page** — replace the mailto CTA with a POST into the existing
   public RFQ API (`rfq_requests`), pre-filled with product SKU/MPN/qty.
4. **Public pricing/offers layer** — surface `marketplace_product_prices`/vendor offers on
   product pages; unlocks add-to-cart + B2B tier display (currently sign-in prompt placeholder).
5. **Seller offers block on product page** — multiple vendors per part ("Seller offers" section).
6. **Alternative parts / generic suggestions** — surface `product_generic_suggestions`
   (module already live server-side via ProductAdmin generic groups).
7. **Datasheet links** — render `product_datasheets` rows on the spec sheet when present.
8. **Admin spec/attribute editor UI** — CRUD over `product_specs` + PR#3 spec-template tables.
9. **Admin charts** — CSP-safe self-hosted sparklines (inline SVG) for dashboard + orders trends.
10. **Web-console RBAC** — map `permission:` middleware onto admin.web routes
    (admin.orders.view/update, admin.chat.view, …) replacing role-only gating.
11. **Blog module** — only after content strategy; archive Blog* is a workable schema blueprint.
12. **Wishlist / compare UI** — needs auth-aware frontend session; pair with pricing layer.
13. **LMS deep-links on product pages** — map categories → `/learn` topics (currently generic link).
