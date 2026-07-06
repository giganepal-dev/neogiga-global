<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * AI Commerce endpoints (Blueprint §13, §29).
 *
 * The tool layer is ready (App\Services\Ai\AiToolsContract +
 * DatabaseAiTools) — all price/stock/product facts come from the
 * database, never from a model. The conversational orchestrator
 * (LLM routing, guardrails, audit, handoff) is Phase 2; no paid AI
 * API is called until ANTHROPIC_API_KEY is configured AND the
 * orchestrator ships. Until then these endpoints return 501.
 */
class AiCommerceController extends Controller
{
    use ApiResponses;

    public function createSession(): JsonResponse
    {
        return $this->notImplemented('AI session', 'Phase 2');
    }

    public function sendMessage(): JsonResponse
    {
        return $this->notImplemented('AI conversation', 'Phase 2');
    }

    public function buildBom(): JsonResponse
    {
        return $this->notImplemented('AI BOM builder', 'Phase 2');
    }

    public function addBomToCart(): JsonResponse
    {
        return $this->notImplemented('AI BOM → cart', 'Phase 2');
    }

    public function createPosInvoice(): JsonResponse
    {
        return $this->notImplemented('AI POS invoice', 'Phase 2');
    }
}
