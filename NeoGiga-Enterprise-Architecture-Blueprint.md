# NeoGiga Enterprise Architecture Blueprint

This file is the living architecture blueprint for NeoGiga. Earlier chapters should continue to preserve the landing page, marketplace architecture, global/regional editions, product catalog, LMS, POS, seller, procurement, and SEO foundations already present in the codebase.

## Chapter 47 - NeoGiga AI Knowledge & Intelligence Platform

### Mission

NeoGiga AI is an engineering intelligence layer for product discovery, datasheet understanding, project design, BOM creation, LMS tutoring, POS assistance, seller support, procurement support, and safe human handoff. It must improve engineering decisions without inventing commercial or safety-critical facts.

### Knowledge Sources

- Product catalog: products, brands, categories, specs, media, documents, compatibility, BOM items, SEO summaries.
- Regional commerce data: marketplace prices, vendor prices, inventory, warehouses, shipping, tax, import duty, delivery zones.
- LMS: courses, lessons, projects, components, code samples, skill levels, product links.
- Datasheets and manuals: source documents, parsed text, chunks, page anchors, extraction confidence.
- Community Q&A: moderated engineering answers, accepted solutions, product links, safety labels.
- Project templates: curated kits, required parts, optional parts, tools, power budgets, wiring overview, difficulty, build time.
- Support and sales transcripts: only after consent, filtering, permission checks, and retention policy.

### Product Knowledge Model

The product knowledge model is catalog-first. AI product facts must resolve to product IDs, SKUs, MPNs, brand IDs, category IDs, marketplace IDs, country IDs, document IDs, or chunk IDs. The model should store source provenance, confidence, freshness, regional scope, and permission scope for every claim.

### Datasheet Ingestion Pipeline

Datasheets move through registered source, document record, malware scan, parser/OCR, normalized metadata, chunking, embedding, review, publication, and periodic refresh. Chunk records must preserve page number, heading path, source URL/file path, checksum, parser version, and citation text.

### LMS Knowledge Model

LMS content is retrieved through courses, lessons, projects, components, code samples, and product links. The AI Tutor can explain concepts and recommend lessons, but it must cite lesson/project records and avoid claiming course availability that is not present in the database.

### Community Q&A Knowledge Model

Community Q&A enters AI retrieval only after moderation. Accepted answers and verified expert answers rank above unverified posts. Safety-sensitive answers require labels and human review before they are eligible for generation.

### Project Template Model

Project templates define required components, optional components, required tools, power requirements, wiring overview, LMS placeholders, sample-code placeholders, product-matching placeholders, safety notes, difficulty, build time, category linkage, and regional availability checks.

### Training And Evaluation

NeoGiga should not fine-tune on private commerce data until governance is mature. Start with retrieval evaluation sets, answer-quality scoring, citation coverage, hallucination reports, and tool-call correctness. Fine-tuning can later use approved public product/LMS content and synthetic tests, never private user orders.

### Feedback Loop

User feedback, support escalations, failed searches, unresolved BOM lines, stale citations, and admin review notes should feed `ai_feedback` and evaluation datasets. Prompt and route changes must be versioned.

### RAG Pipeline

RAG flow: classify task, check permissions, retrieve scoped sources, rerank, generate with citations, validate commercial claims against tools, apply guardrails, log prompt/model/tool data, and request human handoff when confidence or safety rules require it.

### Vector Database Architecture

The schema stores chunk and embedding metadata independently from any vector provider. A future vector adapter may target Postgres pgvector, Qdrant, Weaviate, Pinecone, Elasticsearch, or local FAISS. The database remains the source of provenance and access control.

### Knowledge Graph Architecture

NeoGiga's graph should connect products, brands, categories, specs, compatible parts, substitutes, BOM items, LMS lessons, projects, documents, chunks, vendors, regions, warehouses, and safety tags. The graph should power alternatives, prerequisite lessons, project BOM resolution, and procurement suggestions.

### Tool-Calling Architecture

AI tools expose database/API-backed actions only. Tools include product search, product details, regional inventory, regional price, price tiers, alternatives, BOM creation, BOM line resolution, LMS lessons, tutorials, sample code, cart drafts, quote drafts, tax/shipping calculation, RFQ, payment status, order confirmation, and human handoff.

Commercial rules:

- AI must never invent price, stock, seller offer, delivery time, warranty, tax, shipping, or payment status.
- Payment and order actions require explicit user confirmation.
- Every commercial action must be logged and auditable.
- High-value, dangerous, or ambiguous actions require human review.

### Safety And Guardrails

Guardrails cover prompt injection, data exfiltration, unsafe electronics advice, batteries, mains electricity, robotics, vehicles, drones, industrial automation, minors, regulated imports, high-value orders, and payment/order creation. Sensitive data filtering runs before model calls and before transcript storage.

### Human Handoff

The assistant escalates to support, sales, procurement, or engineering review when confidence is low, safety risk is high, the user requests a human, commercial action exceeds policy, or the model/tool stack is unavailable.

### Continuous Learning

New products, datasheets, LMS content, approved Q&A, support resolutions, and feedback should refresh knowledge sources through the ingestion pipeline. Stale sources are removed from retrieval until refreshed.

### Monitoring

Track latency, token cost, provider errors, route decisions, tool-call success, unresolved BOM lines, citation coverage, user feedback, hallucination reports, safety escalations, and revenue-impacting deflections. Admin AI Console owns monitoring and review.

### Multi-Model Strategy

- Claude: engineering reasoning, long-form planning, safety review, technical synthesis.
- OpenAI: general assistant, structured output, tool planning, embeddings, content generation.
- Gemini: multimodal datasheet/image/video understanding and broad support.
- Qwen: multilingual regional support and cost-sensitive generation.
- DeepSeek: code generation and engineering reasoning where policy allows.
- Local Llama models: private/on-prem support, low-risk summarization, offline fallback.

### Model Routing By Task

| Task | Preferred route |
| --- | --- |
| Engineering reasoning | Claude or OpenAI reasoning model |
| Product search | Database tools plus embedding retriever |
| Code generation | DeepSeek, OpenAI, or Claude with sandboxed review |
| Support | OpenAI or Qwen with handoff policy |
| POS | Small fast model plus strict DB tools |
| Procurement | Claude/OpenAI plus database tools and human approval |
| Safety review | Claude/OpenAI with guardrail rules and escalation |

### LLM Discoverability

NeoGiga should maintain `llms.txt`, clean crawlable HTML summaries, Product schema, FAQ schema, HowTo schema, Course schema, internal links between products/tutorials/projects/LMS/Q&A, and AI-readable source summaries that exclude private commerce endpoints.
