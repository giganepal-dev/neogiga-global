<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * POS requires device auth (OAuth device flow per Blueprint §14),
 * cash-session integrity and the payment layer. Phase 1–2 scope.
 * The pos_* migrations are also still empty shells (DB-02).
 */
class PosController extends Controller
{
    use ApiResponses;

    public function openSession(): JsonResponse
    {
        return $this->notImplemented('POS sessions');
    }

    public function closeSession(): JsonResponse
    {
        return $this->notImplemented('POS sessions');
    }

    public function searchProducts(): JsonResponse
    {
        return $this->notImplemented('POS product search');
    }

    public function createSale(): JsonResponse
    {
        return $this->notImplemented('POS sales');
    }

    public function showSale(int $sale): JsonResponse
    {
        return $this->notImplemented('POS sales');
    }

    public function processPayment(int $sale): JsonResponse
    {
        return $this->notImplemented('POS payments');
    }

    public function processRefund(int $sale): JsonResponse
    {
        return $this->notImplemented('POS refunds');
    }
}
