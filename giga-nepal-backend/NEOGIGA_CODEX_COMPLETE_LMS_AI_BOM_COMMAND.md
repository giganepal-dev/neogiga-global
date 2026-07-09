# NeoGiga Codex Complete LMS AI BOM Command

Goal: wire AI/BOM routes to existing services and finish LMS/AI commerce safely.

Evidence:
- LMS foundation works.
- AI schema/services exist.
- `AiCommerceController` and cart add-BOM return 501.

Tasks:
1. Implement AI session/message endpoints using safe local/tool-unavailable response when provider absent.
2. Implement BOM builder with deterministic fallback.
3. Implement add-BOM-to-cart with server-side product/stock validation.
4. Implement AI POS invoice draft only, no real payment.
5. Ensure all AI recommendations include source notes, confidence, last_updated, and advisory-only disclaimer.

Rules:
- No paid AI call without configured provider.
- No frontend price/stock trust.
- Audit AI actions.

Verification:
- AI routes no longer 501.
- BOM cart test passes.
- Advisory metadata present.

Deliverable:
- `NEOGIGA_LMS_AI_BOM_IMPLEMENTATION_REPORT.md`

