# NeoGiga AI Implementation Roadmap

Date: 2026-07-06

## Phase 1: Safe Foundation

- Add AI platform schema for knowledge, RAG, model routing, prompt versions, guardrails, feedback, evaluations, tool calls, and handoff tickets.
- Expand database-backed AI tool contract with safe stubs.
- Seed curated project templates.
- Add UI-ready placeholder partials.
- Document validation status.

## Phase 2: Orchestrator

- Add provider adapters for OpenAI, Claude, Gemini, Qwen, DeepSeek, and local Llama models.
- Add model router by task, budget, privacy level, latency, and region.
- Add prompt registry with version pinning.
- Add tool dispatcher that writes every call to `ai_tool_calls`.
- Add confirmation gates for quote, RFQ, payment, cart, order, and POS actions.

## Phase 3: RAG Pipeline

- Build source ingestion jobs for product documents, LMS lessons, project templates, FAQs, and moderated Q&A.
- Add parser, chunker, embedding job, vector-store adapter, and citation formatter.
- Add retrieval eval sets for product search, BOM resolution, datasheet facts, LMS tutoring, and safety questions.

## Phase 4: AI Commerce

- Ship AI product assistant, BOM builder, cart draft, quote draft, and human handoff.
- Keep checkout/payment/order creation behind explicit user confirmation and admin review where required.
- Add commercial fact freshness checks and source citations.

## Phase 5: AI Operations

- Admin AI console for model cost, quality, hallucination reports, prompt versions, failed tool calls, eval scores, and handoff queues.
- Continuous learning loop from feedback, support transcripts, product updates, and LMS content.
