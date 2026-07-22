<?php

namespace App\Services\Email\Webhook;

use App\Models\EmailSubscriber;
use App\Models\EmailDeliveryEvent;
use App\Models\EmailCampaign;
use App\Models\EmailSuppression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeliveryWebhookService
{
    protected array $eventTypeMap = [
        'sent' => 'sent',
        'delivered' => 'delivered',
        'open' => 'opened',
        'click' => 'clicked',
        'bounce' => 'bounced',
        'complaint' => 'complained',
        'unsubscribe' => 'unsubscribed',
        'rejected' => 'rejected',
        'deferred' => 'deferred',
        'dropped' => 'dropped',
    ];

    public function handleResendWebhook(array $payload): void
    {
        // Verify Resend signature
        if (!$this->verifyResendSignature($payload)) {
            throw new \Exception('Invalid Resend webhook signature');
        }

        $type = $payload['type'] ?? null;
        $data = $payload['data'] ?? [];

        if (!$type || empty($data)) {
            Log::warning('Invalid Resend webhook payload');
            return;
        }

        $eventType = $this->eventTypeMap[$type] ?? null;
        if (!$eventType) {
            Log::info("Unhandled Resend event type: {$type}");
            return;
        }

        $this->processDeliveryEvent(
            email: $data['email'] ?? null,
            eventType: $eventType,
            provider: 'resend',
            providerEventId: $data['id'] ?? null,
            rawPayload: $payload,
            timestamp: isset($data['created_at']) ? strtotime($data['created_at']) : now()
        );
    }

    public function handleSesWebhook(array $payload): void
    {
        // Verify SES signature
        if (!$this->verifySesSignature($payload)) {
            throw new \Exception('Invalid SES webhook signature');
        }

        $message = json_decode($payload['Message'] ?? '{}', true);
        $eventType = $message['eventType'] ?? null;
        $mail = $message['mail'] ?? [];
        $delivery = $message['delivery'] ?? [];
        $bounce = $message['bounce'] ?? [];
        $complaint = $message['complaint'] ?? [];

        if (!$eventType) {
            Log::warning('Invalid SES webhook payload');
            return;
        }

        $mappedType = $this->mapSesEventType($eventType);
        
        $this->processDeliveryEvent(
            email: $mail['destination'][0] ?? null,
            eventType: $mappedType,
            provider: 'ses',
            providerEventId: $mail['messageId'] ?? null,
            rawPayload: $payload,
            timestamp: isset($mail['timestamp']) ? strtotime($mail['timestamp']) : now(),
            bounceType: $bounce['bounceType'] ?? null,
            bounceSubType: $bounce['bounceSubType'] ?? null,
            complaintType: $complaint['complaintFeedbackType'] ?? null,
        );
    }

    public function handleSmtpWebhook(array $payload): void
    {
        // SMTP webhooks depend on the specific mail server
        // This is a generic handler that should be customized
        
        $eventType = $payload['event'] ?? null;
        $email = $payload['email'] ?? null;

        if (!$eventType || !$email) {
            Log::warning('Invalid SMTP webhook payload');
            return;
        }

        $mappedType = $this->mapSmtpEventType($eventType);

        $this->processDeliveryEvent(
            email: $email,
            eventType: $mappedType,
            provider: 'smtp',
            providerEventId: $payload['message_id'] ?? Str::uuid()->toString(),
            rawPayload: $payload,
            timestamp: $payload['timestamp'] ?? now()
        );
    }

    protected function processDeliveryEvent(
        ?string $email,
        string $eventType,
        string $provider,
        ?string $providerEventId,
        array $rawPayload,
        $timestamp = null,
        ?string $bounceType = null,
        ?string $bounceSubType = null,
        ?string $complaintType = null
    ): void {
        if (!$email) {
            Log::warning('Delivery event missing email address');
            return;
        }

        $email = strtolower(trim($email));

        // Check for duplicate event processing
        if ($providerEventId && EmailDeliveryEvent::where('provider_event_id', $providerEventId)->exists()) {
            Log::debug("Duplicate event ignored: {$providerEventId}");
            return;
        }

        DB::transaction(function () use (
            $email,
            $eventType,
            $provider,
            $providerEventId,
            $rawPayload,
            $timestamp,
            $bounceType,
            $bounceSubType,
            $complaintType
        ) {
            // Find subscriber
            $subscriber = EmailSubscriber::where('email', $email)->first();

            if (!$subscriber) {
                Log::info("Delivery event for unknown subscriber: {$email}");
                // Still log the event
                $this->logDeliveryEvent(null, $eventType, $provider, $providerEventId, $rawPayload, $timestamp);
                return;
            }

            // Log the event
            $this->logDeliveryEvent($subscriber->id, $eventType, $provider, $providerEventId, $rawPayload, $timestamp);

            // Update subscriber stats and status based on event type
            $this->updateSubscriberForEvent($subscriber, $eventType, $bounceType, $bounceSubType, $complaintType);
        });
    }

    protected function logDeliveryEvent(
        ?int $subscriberId,
        string $eventType,
        string $provider,
        ?string $providerEventId,
        array $rawPayload,
        $timestamp = null
    ): void {
        EmailDeliveryEvent::create([
            'subscriber_id' => $subscriberId,
            'event_type' => $eventType,
            'provider' => $provider,
            'provider_event_id' => $providerEventId,
            'payload' => $rawPayload,
            'processed_at' => $timestamp ?? now(),
        ]);
    }

    protected function updateSubscriberForEvent(
        EmailSubscriber $subscriber,
        string $eventType,
        ?string $bounceType = null,
        ?string $bounceSubType = null,
        ?string $complaintType = null
    ): void {
        $updates = [];

        switch ($eventType) {
            case 'delivered':
                $updates['total_delivered'] = ($subscriber->total_delivered ?? 0) + 1;
                $updates['last_email_sent_at'] = now();
                break;

            case 'opened':
                $updates['total_opened'] = ($subscriber->total_opened ?? 0) + 1;
                $updates['last_opened_at'] = now();
                $updates['engagement_score'] = min(100, ($subscriber->engagement_score ?? 0) + 5);
                break;

            case 'clicked':
                $updates['total_clicked'] = ($subscriber->total_clicked ?? 0) + 1;
                $updates['last_clicked_at'] = now();
                $updates['engagement_score'] = min(100, ($subscriber->engagement_score ?? 0) + 10);
                break;

            case 'bounced':
                $updates['total_bounced'] = ($subscriber->total_bounced ?? 0) + 1;
                
                // Hard bounce - suppress immediately
                if ($bounceType === 'HardBounce' || $bounceSubType === 'General') {
                    $updates['status'] = EmailSubscriber::STATUS_BOUNCED;
                    $this->suppressEmail($subscriber->email, 'hard_bounce');
                } elseif ($bounceType === 'SoftBounce') {
                    // Soft bounce - check threshold
                    if ($updates['total_bounced'] >= config('email.bounce_threshold', 3)) {
                        $updates['status'] = EmailSubscriber::STATUS_BOUNCED;
                        $this->suppressEmail($subscriber->email, 'soft_bounce_threshold');
                    }
                }
                break;

            case 'complained':
                $updates['total_complaints'] = ($subscriber->total_complaints ?? 0) + 1;
                $updates['status'] = EmailSubscriber::STATUS_COMPLAINED;
                $this->suppressEmail($subscriber->email, 'complaint');
                break;

            case 'unsubscribed':
                $updates['status'] = EmailSubscriber::STATUS_UNSUBSCRIBED;
                $updates['unsubscribed_at'] = now();
                break;

            case 'rejected':
            case 'dropped':
                $updates['status'] = EmailSubscriber::STATUS_SUPPRESSED;
                $this->suppressEmail($subscriber->email, 'rejected');
                break;
        }

        if (!empty($updates)) {
            $subscriber->update($updates);
        }
    }

    protected function suppressEmail(string $email, string $reason): void
    {
        EmailSuppression::firstOrCreate(
            ['email' => $email],
            [
                'reason' => $reason,
                'status' => 'active',
                'source' => 'webhook',
            ]
        );
    }

    protected function mapSesEventType(string $sesType): string
    {
        $map = [
            'Send' => 'sent',
            'Delivery' => 'delivered',
            'Open' => 'opened',
            'Click' => 'clicked',
            'Bounce' => 'bounced',
            'Complaint' => 'complained',
            'Reject' => 'rejected',
        ];

        return $map[$sesType] ?? 'unknown';
    }

    protected function mapSmtpEventType(string $smtpType): string
    {
        $map = [
            'sent' => 'sent',
            'delivered' => 'delivered',
            'open' => 'opened',
            'click' => 'clicked',
            'bounce' => 'bounced',
            'complaint' => 'complained',
            'unsubscribe' => 'unsubscribed',
        ];

        return $map[$smtpType] ?? 'unknown';
    }

    protected function verifyResendSignature(array $payload): bool
    {
        // Implement Resend webhook signature verification
        // Uses the webhook signing secret from config
        $secret = config('services.resend.webhook_secret');
        
        if (!$secret) {
            Log::warning('Resend webhook secret not configured');
            return true; // Allow in development
        }

        // Implementation depends on Resend's signature method
        // Typically involves verifying a signature header
        
        return true; // TODO: Implement proper verification
    }

    protected function verifySesSignature(array $payload): bool
    {
        // Implement AWS SES webhook signature verification
        // Uses AWS SDK to verify SNS message authenticity
        
        return true; // TODO: Implement proper verification with AWS SDK
    }
}
