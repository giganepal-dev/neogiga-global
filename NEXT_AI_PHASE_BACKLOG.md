# Next AI Phase Backlog

Date: 2026-07-06

## Highest Priority

- Implement `AiToolDispatcher` that wraps every `AiToolsContract` call and writes `ai_tool_calls`.
- Add `AiPermissionPolicy` for user, org, role, marketplace, country, and tool-level checks.
- Add prompt-injection scanner and sensitive-data filter.
- Add `AiKnowledgeIngestion` jobs for product documents and LMS content.
- Choose vector backend and implement adapter.
- Add admin AI console screens for tool calls, handoffs, evals, feedback, prompts, providers, and model routes.
- Add confirmation UX for cart, quote, RFQ, payment, and order actions.

## Product AI

- Product page assistant with citations to specs, documents, reviews, LMS, and compatible parts.
- Alternative-parts resolver.
- Regional price/stock explanation that cites database tool calls.
- Datasheet summarizer with page citations.

## Project AI

- BOM line resolver against catalog and alternatives.
- Project builder flow from seeded templates.
- Safety checklist generator for batteries, mains, drones, vehicles, robotics, and industrial automation.
- LMS lesson recommendations tied to required components.

## Operations

- Retrieval eval sets.
- Hallucination reporting workflow.
- Provider-cost dashboards.
- Quality scoring per route/provider/prompt version.
