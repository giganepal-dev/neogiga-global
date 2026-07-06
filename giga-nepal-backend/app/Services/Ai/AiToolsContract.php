<?php

namespace App\Services\Ai;

/**
 * Tool surface exposed to NeoGiga AI agents (Blueprint §13, §29).
 *
 * HARD RULES (enforced by every implementation):
 *  - AI must NEVER invent price or stock. Every commercial fact returned
 *    here comes from the database / catalog API. If a value is unknown,
 *    return null/empty — never an estimate.
 *  - Tools execute under the *user's* permission context once auth lands
 *    (Blueprint §13 "Permissions"); no tool may read data the user cannot.
 *  - Every tool call will be audited by the Phase-2 orchestrator.
 *
 * The LLM orchestrator (Claude API routing, guardrails, memory, audit,
 * human handoff) is Phase 2; this contract lets it plug in without
 * touching commerce code.
 */
interface AiToolsContract
{
    /**
     * Full-text product search scoped to a marketplace.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function searchProducts(string $query, ?int $marketplaceId = null, int $limit = 10): array;

    /**
     * Canonical product facts (specs, brand, category, media, price if priced).
     */
    public function getProductDetails(int $productId, ?int $marketplaceId = null): ?array;

    /**
     * Regional availability. Quantities come straight from inventory_stocks.
     *
     * @return array{product_id: int, total_available: int, locations: array<int, array<string, mixed>>}
     */
    public function getRegionalInventory(int $productId, ?int $marketplaceId = null): array;

    /** Database-backed regional price lookup. Null means unknown/unpriced. */
    public function getRegionalPrice(int $productId, ?int $marketplaceId = null): array;

    /** Bulk/contract tiers if configured; [] means no tiers. */
    public function getPriceTiers(int $productId, ?int $marketplaceId = null): array;

    /** Catalog alternatives/substitutes only; never invented. */
    public function findAlternativeParts(int $productId, ?int $marketplaceId = null, int $limit = 10): array;

    /**
     * Persist a structured BOM draft for a described project.
     * Line prices are resolved from the catalog; unresolved lines carry
     * product_id = null and price = null (never guessed).
     */
    public function createProjectBOM(string $goalDescription, array $lines, ?int $userId = null): array;

    /** Resolve one requested BOM line against the catalog. */
    public function resolveBOMLine(string $requestedPart, int $quantity = 1, ?int $marketplaceId = null): array;

    /**
     * LMS lessons/projects relevant to a topic or product set.
     * Returns [] until the LMS schema lands (DB-02) — never fabricated content.
     */
    public function findLMSLessons(string $topic, int $limit = 5): array;

    /** Tutorial/project content relevant to a topic. */
    public function findTutorials(string $topic, int $limit = 5): array;

    /** Sample code from LMS/code-sample records only. */
    public function generateSampleCode(string $topic, array $constraints = []): array;

    /** Create (or fetch) the user's active cart. */
    public function createCart(int $userId, ?int $marketplaceId = null): array;

    /** Create an auditable cart draft, not a live checkout. */
    public function createCartDraft(int $userId, array $items, ?int $marketplaceId = null): array;

    /** Update an auditable cart draft, not a live checkout. */
    public function updateCartDraft(string $draftReference, array $items): array;

    /** Calculate only through configured database rules; unavailable if rules are incomplete. */
    public function calculateTaxShipping(int $userId, array $items, ?int $marketplaceId = null): array;

    /**
     * Draft quote from BOM/cart lines. Phase-1 dependent (pricing rules);
     * implementations must throw AiToolUnavailableException, not fake it.
     */
    public function createQuote(int $userId, array $lines): array;

    /** Create a quote draft; no seller offer is final until human/system approval. */
    public function createQuoteDraft(int $userId, array $lines): array;

    /** RFQ creation is gated until procurement workflow exists. */
    public function createRFQ(int $userId, array $lines): array;

    /**
     * Payment link creation — requires the payment adapter layer (Phase 1).
     * Implementations must throw AiToolUnavailableException, not fake it.
     */
    public function createPaymentLink(int $orderId): array;

    /** Payment status must come from payment provider/database only. */
    public function checkPaymentStatus(int $paymentId): array;

    /** Order creation after explicit confirmation; unavailable until checkout policy ships. */
    public function createOrderAfterConfirmation(int $userId, string $confirmationReference): array;

    /**
     * Escalate the conversation to a human (support/sales queue).
     * Always available: logs the request and returns a reference.
     */
    public function handoffToHuman(?int $userId, string $reason, array $context = []): array;
}
