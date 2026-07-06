# NeoGiga AI Data Pipeline Gap Report

Date: 2026-07-06

## Target Pipeline

1. Source registration: catalog, datasheet, LMS, community Q&A, support transcript, project template.
2. Permission and regional scope tagging.
3. Document fetch/upload verification.
4. Malware and sensitive-data scan.
5. Parse/OCR/extract.
6. Normalize product IDs, SKUs, MPNs, categories, brands, country, marketplace, and language.
7. Chunk with citation anchors.
8. Embed with provider/model metadata.
9. Store chunks and vectors.
10. Evaluate retrieval quality.
11. Publish to AI retriever only after approval.
12. Monitor drift, stale pricing/stock references, hallucination reports, and cost.

## Current State

The codebase has catalog, product document, LMS, pricing, inventory, and AI-commerce shell tables. It does not yet have ingestion jobs, chunking, embeddings, vector-store drivers, eval sets, or admin review workflow.

## Required Jobs

- `IngestAiKnowledgeSource`
- `ParseAiDocument`
- `ChunkAiDocument`
- `EmbedAiDocumentChunks`
- `EvaluateAiRetrievalSet`
- `RefreshAiSourceProvenance`
- `ExpireStaleCommercialContext`

## Required Policies

- Reject public crawling of cart, checkout, order, payment, admin, seller-private, and user-private records.
- Treat price, stock, seller offer, delivery time, tax, shipping, warranty, and payment as live tool-only facts.
- Require human escalation for batteries, mains electricity, drones, vehicles, industrial automation, high-value orders, and uncertain safety context.
- Keep regional facts scoped by marketplace/country.
