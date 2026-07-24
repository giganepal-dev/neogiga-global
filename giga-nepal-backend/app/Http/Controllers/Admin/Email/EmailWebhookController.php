<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailWebhookController extends Controller
{
    public function resend(Request $request): JsonResponse
    {
        if (! $this->verifyResendSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->json()->all();
        $eventType = $payload['type'] ?? 'unknown';
        $email = $payload['data']?['email'] ?? null;
        $campaignId = $payload['data']?['campaign_id'] ?? null;
        $subscriberId = $payload['data']?['subscriber_id'] ?? null;

        if ($email && in_array($eventType, ['email.bounced', 'email.complained'])) {
            $this->addSuppression($email, $eventType, 'resend');
        }

        if ($subscriberId) {
            $this->logDeliveryEvent($subscriberId, $campaignId, $eventType);
        }

        return response()->json(['status' => 'ok']);
    }

    public function ses(Request $request): JsonResponse
    {
        $message = $request->input('Message');
        if (! $message) {
            return response()->json(['error' => 'No message'], 400);
        }

        $notification = json_decode($message, true);
        $eventType = $notification['eventType'] ?? 'unknown';
        $mail = $notification['mail'] ?? [];
        $headers = $mail['commonHeaders'] ?? [];

        $email = $headers['from'][0] ?? null;
        $campaignId = $mail['messageId'] ?? null;

        if (in_array($eventType, ['Bounce', 'Complaint'])) {
            $bounceType = $notification['bounce']?['bounceType'] ?? 'unknown';
            $email = $notification['bounce']?['bouncedRecipients'][0]?['emailAddress'] ?? $email;

            if ($email) {
                $this->addSuppression($email, strtolower($eventType), 'ses', $bounceType === 'Permanent');
            }
        }

        if (in_array($eventType, ['Open', 'Click'])) {
            $subscriberId = $this->findSubscriberByEmail($email);
            if ($subscriberId) {
                $this->logDeliveryEvent($subscriberId, $campaignId, strtolower($eventType));
            }
        }

        return response()->json(['status' => 'ok']);
    }

    public function generic(Request $request, string $provider): JsonResponse
    {
        $signatureHeader = $request->header('X-Webhook-Signature') ?? $request->header('X-Hub-Signature-256');

        if ($signatureHeader && ! $this->verifyGenericSignature($request, $signatureHeader, $provider)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->json()->all();
        $eventType = $payload['event'] ?? $payload['type'] ?? 'unknown';
        $email = $payload['data']?['email'] ?? $payload['email'] ?? null;
        $campaignId = $payload['data']?['campaign_id'] ?? $payload['campaign_id'] ?? null;
        $subscriberId = $payload['data']?['subscriber_id'] ?? $payload['subscriber_id'] ?? null;

        if (in_array(strtolower($eventType), ['bounce', 'complaint', 'unsubscribe'])) {
            if ($email) {
                $this->addSuppression($email, strtolower($eventType), $provider);
            }
        }

        if ($subscriberId) {
            $this->logDeliveryEvent($subscriberId, $campaignId, $eventType);
        }

        DB::table('email_audit_logs')->insert([
            'action' => 'webhook_received',
            'entity_type' => 'email_provider',
            'details' => json_encode([
                'provider' => $provider,
                'event_type' => $eventType,
                'email' => $email,
            ]),
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'ok']);
    }

    private function verifyResendSignature(Request $request): bool
    {
        $secret = config('services.resend.webhook_secret');
        if (! $secret) {
            return true;
        }

        $signature = $request->header('svix-signature');
        if (! $signature) {
            return false;
        }

        $body = $request->getContent();
        $expectedHash = hash_hmac('sha256', $body, $secret);

        return Str::contains($signature, $expectedHash);
    }

    private function verifyGenericSignature(Request $request, string $signature, string $provider): bool
    {
        $secret = config("services.{$provider}.webhook_secret");
        if (! $secret) {
            return true;
        }

        $body = $request->getContent();
        $expectedHash = hash_hmac('sha256', $body, $secret);

        if (Str::startsWith($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        return hash_equals($expectedHash, $signature);
    }

    private function addSuppression(string $email, string $reason, string $source, bool $permanent = true): void
    {
        $exists = DB::table('email_suppressions_extension')
            ->where('email', $email)
            ->exists();

        if (! $exists) {
            DB::table('email_suppressions_extension')->insert([
                'email' => $email,
                'reason' => $reason,
                'source' => $source,
                'is_global' => true,
                'is_permanent' => $permanent,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function logDeliveryEvent(?int $subscriberId, ?string $campaignId, string $eventType): void
    {
        if (! $subscriberId) {
            return;
        }

        DB::table('email_delivery_logs')->insert([
            'subscriber_id' => $subscriberId,
            'campaign_id' => $campaignId,
            'status' => $eventType,
            'event_type' => $eventType,
            'metadata' => json_encode(['source' => 'webhook']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function findSubscriberByEmail(?string $email): ?int
    {
        if (! $email) {
            return null;
        }

        return DB::table('email_subscribers')->where('email', $email)->value('id');
    }
}
