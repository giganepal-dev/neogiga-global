# 07 AI Readiness Audit

## Executive Summary

NeoGiga has a solid AI foundation but not a working AI product. The repository includes AI commerce models, AI schema extensions, an AI tool contract, database-backed tool methods, RAG-oriented tables, guardrail/model-route/prompt-version foundations, project template seed data, llms.txt, and UI placeholders. The orchestrator, retrieval pipeline, vector database, provider adapters, prompt execution, tool dispatcher, evaluations, and admin AI console are not implemented.

## Current Status

- AI tool interface: `app/Services/Ai/AiToolsContract.php`.
- DB-backed tool implementation: `app/Services/Ai/DatabaseAiTools.php`.
- AI routes: `/api/v1/ai/*`, all returning `501`.
- AI knowledge platform migration exists.
- AI project templates seeder exists.
- Config supports provider env placeholders.

## Completed

- Product search/details/inventory/price tool methods.
- Safe unavailable exceptions for payment/order/RFQ/tax flows.
- AI knowledge source/document/chunk/embedding schema.
- Prompt, provider, route, guardrail, feedback, evaluation schema.
- Project template seed data.
- Commercial fact guardrails documented in `llms.txt`.

## Partially Completed

- Tool calling: contract exists, but no dispatcher/audit wrapper executes calls in a conversation.
- RAG: tables exist, but no ingestion/chunking/embedding/retrieval.
- Knowledge graph: conceptual only.
- Conversation memory: tables exist, but no runtime memory/orchestrator.
- AI UI: placeholders only.

## Missing

- LLM provider adapters.
- Model router.
- Prompt registry runtime.
- Vector DB implementation.
- Ingestion jobs.
- Reranking and citation enforcement.
- AI safety scanner.
- Tool permission checks.
- AI audit log enforcement.
- Admin AI console.
- Evaluation suite.

## Risk

Medium-high. The foundation is good, but exposing AI before tool permissions, citations, and commercial guardrails are enforced would create hallucination and order/payment risk.

## Evidence

- `AI_READINESS_AUDIT.md`
- `AI_KNOWLEDGE_GAP_REPORT.md`
- `database/migrations/marketplace/2026_07_06_100000_create_ai_knowledge_platform_tables.php`
- `app/Services/Ai/AiToolsContract.php`
- `app/Services/Ai/DatabaseAiTools.php`
- `app/Http/Controllers/Api/AI/AiCommerceController.php`
- `database/seeders/AiProjectTemplateSeeder.php`

## Recommendation

Next AI phase should build an audited tool dispatcher, permission policy, source ingestion jobs, vector adapter, citation formatter, model provider adapter layer, and admin review console before enabling chat.

## Priority

P0: Tool dispatcher, permissions, audit logging.  
P1: RAG ingestion/vector/citations.  
P2: Provider routing and evaluations.  
P3: AI UI surfaces.

## Estimated Effort

6-10 weeks for safe AI MVP.  
4-6 months for enterprise AI knowledge platform.

