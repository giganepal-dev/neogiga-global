# NeoGiga AI Knowledge Gap Report

Date: 2026-07-06

## Knowledge Sources

NeoGiga already has source domains that can become a strong RAG corpus:

- Product catalog: products, brands, categories, specs, images, documents, compatibility, BOM items.
- Regional commerce facts: marketplace prices, vendor prices, inventory stocks, warehouses, tax and shipping rules.
- LMS: courses, lessons, projects, project components, code samples, product links.
- POS and sales: POS sessions, sales, invoices, payments, refunds.
- Import/export: product and catalog ingestion history.
- Community/Q&A: not present yet.
- Support/handoff: not present as durable AI tickets yet.

## Critical Gaps

| Gap | Risk | Required foundation |
| --- | --- | --- |
| No canonical knowledge source registry | AI cannot cite source type, owner, country, quality, or refresh state. | `ai_knowledge_sources` |
| No document ingestion state | Datasheets cannot be tracked from upload to parse to embedding. | `ai_documents` |
| No chunk model | RAG cannot cite exact passage/chunk. | `ai_document_chunks` |
| No embedding metadata | Vector search cannot be reproduced or evaluated. | `ai_embeddings` |
| No model route registry | Tasks cannot be safely routed by cost, privacy, or reasoning need. | `ai_model_providers`, `ai_model_routes` |
| No prompt versioning | Behavior changes cannot be audited or rolled back. | `ai_prompt_versions` |
| No eval/feedback loop | Quality cannot be measured over time. | `ai_evaluations`, `ai_feedback` |
| No guardrail registry | Prompt-injection and commercial-action rules are not centrally managed. | `ai_guardrail_rules` |
| No durable tool-call audit | AI actions cannot be reviewed or reconstructed. | `ai_tool_calls`, `ai_order_actions` |

## Citation Policy

Every AI answer that contains product, commercial, technical, safety, or learning claims must carry source provenance:

- Product claims cite product/spec/document records.
- Price and stock cite pricing/inventory tool calls.
- LMS claims cite course/lesson/project records.
- Datasheet facts cite document chunks.
- Project-template facts cite `ai_project_templates`.
- User-specific commercial actions cite auditable tool calls and explicit confirmation events.

## Retrieval Priority

1. Exact product SKU, MPN, brand, category, and regional marketplace match.
2. Datasheet chunks from approved product documents.
3. LMS lessons and code samples linked to the product/category.
4. Community Q&A once moderated.
5. General model knowledge only for non-commercial explanation, never for price, stock, offer, warranty, delivery, or safety-critical claims.
