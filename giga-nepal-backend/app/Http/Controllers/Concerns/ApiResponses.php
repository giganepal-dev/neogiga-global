<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    protected function success(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    /**
     * Structured 501 for endpoints that are routed by design (API contract is
     * stable) but whose implementation is scheduled for a later phase.
     * Replaces the previous behaviour of fataling on missing methods.
     */
    protected function notImplemented(string $feature, string $phase = 'Phase 1'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'not_implemented',
            'message' => "{$feature} is not available yet (scheduled for {$phase}). "
                . 'See NEXT_PHASE_BACKLOG.md.',
        ], 501);
    }
}
