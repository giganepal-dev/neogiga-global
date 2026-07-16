<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Concerns\HandlesCommerceAiBomCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommerceAi\CommerceAiPromptRequest;
use App\Services\CommerceAi\CommerceAiBomCartService;
use App\Services\CommerceAi\CommerceAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AI Commerce endpoints (Blueprint §13, §29).
 *
 * The legacy contract delegates its advisory work to the bounded local
 * Commerce AI service. It does not call a paid provider or create an order,
 * payment, stock reservation, or POS invoice.
 */
class AiCommerceController extends Controller
{
    use ApiResponses;
    use HandlesCommerceAiBomCart;

    public function createSession(Request $request, CommerceAiService $ai): JsonResponse
    {
        return $this->success($ai->createSession($request->user()?->id), 201);
    }

    public function sendMessage(CommerceAiPromptRequest $request, CommerceAiService $ai): JsonResponse
    {
        $data = $request->validated();

        return $this->success($ai->respond(
            $data['prompt'],
            $data['session_key'] ?? null,
            $request->user()?->id,
        ));
    }

    public function buildBom(CommerceAiPromptRequest $request, CommerceAiService $ai): JsonResponse
    {
        $data = $request->validated();

        return $this->success($ai->buildBom(
            $data['prompt'],
            $data['session_key'] ?? null,
            $request->user()?->id,
        ), 201);
    }

    public function addBomToCart(Request $request, CommerceAiBomCartService $cart): JsonResponse
    {
        return $this->addCommerceAiBomToCart($request, $cart);
    }

    public function createPosInvoice(): JsonResponse
    {
        return $this->notImplemented('AI POS invoice', 'Phase 2');
    }
}
