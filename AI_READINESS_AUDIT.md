# NeoGiga AI Readiness Audit

Date: 2026-07-06

## Executive Status

NeoGiga has a useful AI-commerce foundation, but it is not yet a production AI knowledge platform. The current Laravel backend includes marketplace, product, inventory, pricing, cart, POS, LMS shell, AI session/message/BOM/cart-action tables, an AI controller returning safe `501` placeholders, and a database-backed `AiToolsContract` implementation. This is the correct foundation because commercial facts can be read from database tools instead of invented by an LLM.

The missing pieces are RAG ingestion, document chunking, embeddings, model routing, prompt versioning, evaluations, feedback loops, guardrail rules, auditable tool calls, human handoff records, project templates, and UI placeholders.

## Findings

| Area | Current readiness | Gap |
| --- | --- | --- |
| AI models/services | `App\Services\Ai\AiToolsContract` and `DatabaseAiTools` exist. | No orchestrator, model provider registry, model routing, prompt versioning, evals, or live provider adapter. |
| AI chat structures | `ai_sessions` and `ai_messages` migrations exist. | No formal `ai_conversations` table, conversation ownership policy, or transcript retention policy. |
| AI database tables | AI-commerce shells exist for sessions, messages, BOM, cart actions, LMS recommendations, POS invoices. | No knowledge, embeddings, providers, routes, guardrails, feedback, or tool-call audit tables. |
| Product catalog linkage | Product, inventory, marketplace price, BOM, documents, and LMS link models exist. | AI citations need source-level product/document provenance and chunk references. |
| LMS linkage | LMS course, lesson, project, component, product-link, and code-sample shells exist. | No populated LMS corpus or AI tutor retrieval contract. |
| Datasheet/document handling | `product_documents` table exists. | No ingestion status, checksums, chunking, OCR, embedding, citation, or source quality metadata. |
| Vector database readiness | Not present. | Needs vector store abstraction, embedding metadata, chunk table, and provider config. |
| RAG readiness | Not present beyond source tables. | Needs ingestion pipeline, retriever, reranker, citation enforcement, and eval sets. |
| Tool calling readiness | Good start through `AiToolsContract`. | Needs full tool-call audit records, permission checks, and explicit confirmation gates. |
| AI POS readiness | POS and AI POS placeholder exist. | No cashier role checks, device/session policy, or live payment action guardrails. |
| AI BOM readiness | BOM builder service/table shells exist. | Needs project templates, line resolution, alternatives, safety notes, and quote/cart draft flow. |
| AI Project Builder readiness | LMS project shells exist. | Needs curated template seed data and product matching workflow. |
| AI audit logging | Application log handoff exists. | Needs durable `ai_tool_calls`, `ai_order_actions`, `ai_handoff_tickets`, and review states. |
| Security/permissions | Admin token middleware and rate limiters exist. | Needs role-scoped tool permissions, prompt injection checks, sensitive-data filtering, and dangerous-order escalation. |

## Immediate Safe Foundation Added

- Chapter 47 blueprint documentation for the AI Knowledge and Intelligence Platform.
- Dedicated AI platform migration covering knowledge, RAG, model routing, guardrails, feedback, and auditable commercial action tables.
- Project template seeder with ten requested engineering kits.
- Expanded AI tool interface and DB-backed safe stubs for product, inventory, price, LMS, code, cart, quote, RFQ, payment, order, and human handoff workflows.
- UI-ready Blade placeholder partial for the requested assistant surfaces.

## Not Implemented Deliberately

- No paid AI provider calls.
- No live checkout, payment link creation, order creation, or RFQ automation.
- No fabricated price, stock, seller offer, delivery time, datasheet facts, or LMS content.
- No vector database driver selected until infrastructure is chosen.
