<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Services\Email\Webhook\DeliveryWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailWebhookController extends Controller
{
    protected DeliveryWebhookService $webhookService;

    public function __construct(DeliveryWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function handleResend(Request $request)
    {
        try {
            $payload = $request->all();
            
            Log::info('Received Resend webhook', ['type' => $payload['type'] ?? 'unknown']);

            // Queue the webhook processing for async handling
            \App\Jobs\Email\Webhook\ProcessDeliveryWebhook::dispatch(
                'resend',
                $payload
            )->onQueue('emails-webhooks');

            return response()->json(['success' => true], 200);
            
        } catch (\Exception $e) {
            Log::error('Resend webhook processing failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function handleSes(Request $request)
    {
        try {
            $payload = $request->all();
            
            // SNS subscription confirmation
            if (isset($payload['Type']) && $payload['Type'] === 'SubscriptionConfirmation') {
                $topicArn = $payload['TopicArn'] ?? null;
                $token = $payload['Token'] ?? null;
                
                if ($topicArn && $token) {
                    // Confirm the subscription
                    $client = \Aws\Sns\SnsClient::factory([
                        'version' => 'latest',
                        'region' => config('services.ses.region', 'us-east-1'),
                    ]);
                    
                    $client->confirmSubscription([
                        'TopicArn' => $topicArn,
                        'Token' => $token,
                    ]);
                    
                    Log::info("SNS subscription confirmed for topic: {$topicArn}");
                }
                
                return response()->json(['success' => true], 200);
            }
            
            Log::info('Received SES webhook', ['eventType' => json_decode($payload['Message'] ?? '{}', true)['eventType'] ?? 'unknown']);

            // Queue the webhook processing
            \App\Jobs\Email\Webhook\ProcessDeliveryWebhook::dispatch(
                'ses',
                $payload
            )->onQueue('emails-webhooks');

            return response()->json(['success' => true], 200);
            
        } catch (\Exception $e) {
            Log::error('SES webhook processing failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function handleSmtp(Request $request)
    {
        try {
            $payload = $request->all();
            
            Log::info('Received SMTP webhook', ['event' => $payload['event'] ?? 'unknown']);

            // Queue the webhook processing
            \App\Jobs\Email\Webhook\ProcessDeliveryWebhook::dispatch(
                'smtp',
                $payload
            )->onQueue('emails-webhooks');

            return response()->json(['success' => true], 200);
            
        } catch (\Exception $e) {
            Log::error('SMTP webhook processing failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
