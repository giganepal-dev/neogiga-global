<?php

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Jobs\Marketing\ProcessEmailWebhookJob;
use App\Services\Marketing\EmailWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;
use RuntimeException;

class EmailWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, EmailWebhookService $webhooks): JsonResponse
    {
        $raw = $request->getContent();
        if ($raw === '' || strlen($raw) > 2_000_000) {
            return response()->json(['message' => 'Invalid payload.'], 422);
        }
        $signature = $request->header('X-NeoGiga-Signature') ?: $request->header('X-Webhook-Signature') ?: $request->header('X-Signature');
        try {
            $result = $webhooks->ingest($provider, $raw, $signature);
        } catch (JsonException) {
            return response()->json(['message' => 'Invalid JSON payload.'], 422);
        } catch (RuntimeException) {
            return response()->json(['message' => 'Webhook verification failed.'], 401);
        }
        foreach ($result['event_ids'] as $eventId) {
            ProcessEmailWebhookJob::dispatch($eventId);
        }

        return response()->json(['accepted' => true, 'received' => $result['received']], 202);
    }
}
