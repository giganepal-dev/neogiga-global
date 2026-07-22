<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Services\Email\DeliveryWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EmailWebhookController extends Controller
{
    protected DeliveryWebhookService $webhookService;

    public function __construct(DeliveryWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle Resend Webhooks
     * https://resend.com/docs/api/webhooks
     */
    public function resend(Request $request): Response
    {
        $payload = $request->all();
        $signature = $request->header('X-Resend-Signature'); // Verify if implemented in middleware
        
        Log::channel('email-webhooks')->info('Resend webhook received', ['type' => $payload['type'] ?? 'unknown']);

        // Queue for async processing to ensure quick response to provider
        \App\Jobs\Email\ProcessDeliveryWebhook::dispatch(
            'resend',
            $payload,
            $signature
        )->onQueue('emails-webhooks');

        return response()->json(['status' => 'processed']);
    }

    /**
     * Handle Amazon SES Webhooks (SNS)
     */
    public function ses(Request $request): Response
    {
        $payload = $request->all();
        
        // SNS Subscription Confirmation
        if (isset($payload['Type']) && $payload['Type'] === 'SubscriptionConfirmation') {
            $topicArn = $payload['TopicArn'];
            $token = $payload['Token'];
            $subscribeUrl = "https://sns.{$this->getAwsRegion()}.amazonaws.com/?Action=ConfirmSubscription&TopicArn={$topicArn}&Token={$token}";
            
            Log::channel('email-webhooks')->info('SES SNS Confirmation Required', ['url' => $subscribeUrl]);
            // In production, automatically curl this URL or alert admin
            return response()->json(['status' => 'confirmation_required', 'url' => $subscribeUrl]);
        }

        // Actual Event Notification
        if (isset($payload['Message'])) {
            $message = json_decode($payload['Message'], true);
            Log::channel('email-webhooks')->info('SES event received', ['type' => $message['eventType'] ?? 'unknown']);
            
            \App\Jobs\Email\ProcessDeliveryWebhook::dispatch(
                'ses',
                $message,
                $request->header('X-Amz-Sns-Message-Type')
            )->onQueue('emails-webhooks');
        }

        return response()->json(['status' => 'processed']);
    }

    /**
     * Handle Generic SMTP / Mailgun / Sendgrid style webhooks
     */
    public function generic(Request $request, string $provider): Response
    {
        $payload = $request->all();
        $signature = $request->header('X-Signature'); // Provider specific
        
        Log::channel('email-webhooks')->info("{$provider} webhook received");

        \App\Jobs\Email\ProcessDeliveryWebhook::dispatch(
            $provider,
            $payload,
            $signature
        )->onQueue('emails-webhooks');

        return response()->json(['status' => 'processed']);
    }

    private function getAwsRegion(): string
    {
        return config('services.ses.region', 'us-east-1');
    }
}
