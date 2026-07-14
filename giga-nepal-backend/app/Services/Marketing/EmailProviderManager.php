<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailProviderManager
{
    public function __construct(
        private RegionalEmailBrandingService $branding,
        private EmailProviderConfigurationService $configuration,
    ) {}

    public function provider(): string
    {
        $this->configuration->apply('transactional');

        return (string) config('marketing.transactional.mailer', 'log');
    }

    public function testMode(): bool
    {
        $this->configuration->apply('transactional');

        return (bool) config('marketing.transactional.test_mode', true);
    }

    public function send(array $message): array
    {
        $this->configuration->apply('transactional');
        if (! config('marketing.transactional.enabled', false) || $this->testMode()) {
            return ['provider' => $this->provider(), 'status' => 'test_queued', 'provider_message_id' => null, 'sandbox' => true];
        }
        $branding = $this->branding->context(isset($message['marketplace_id']) ? (int) $message['marketplace_id'] : null, 'transactional');
        if (! $branding['verified'] || ! $branding['enabled'] || ! $branding['from_email']) {
            return ['provider' => $this->provider(), 'status' => 'failed', 'provider_message_id' => null, 'retryable' => false, 'failure_code' => 'sender_not_verified', 'failure_reason' => 'The regional transactional sender profile is not verified and enabled.'];
        }
        try {
            Mail::mailer($this->provider())->html((string) ($message['html_body'] ?? $message['text_body'] ?? ''), function ($mail) use ($message, $branding) {
                $mail->to((string) $message['to_email'])->subject((string) $message['subject'])->from($branding['from_email'], $branding['from_name']);
                if ($branding['reply_to']) {
                    $mail->replyTo($branding['reply_to'], $branding['from_name']);
                }
            });

            return ['provider' => $this->provider(), 'status' => 'sent', 'provider_message_id' => null, 'sandbox' => false];
        } catch (Throwable $exception) {
            return ['provider' => $this->provider(), 'status' => 'failed', 'provider_message_id' => null, 'retryable' => true, 'failure_code' => 'transport_error', 'failure_reason' => $exception->getMessage()];
        }
    }

    public function testConnection(?string $recipient = null, ?int $marketplaceId = null): array
    {
        $configured = mb_strtolower(trim((string) config('marketing.transactional.test_recipient')));
        $recipient = mb_strtolower(trim((string) ($recipient ?: $configured)));
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL) || $configured === '' || ! hash_equals($configured, $recipient)) {
            return ['provider' => $this->provider(), 'status' => 'failed', 'sandbox' => $this->testMode(), 'failure_code' => 'test_recipient_not_allowed', 'failure_reason' => 'Transactional connection tests require the configured test recipient.'];
        }

        return $this->send([
            'to_email' => $recipient,
            'subject' => 'NeoGiga transactional transport test',
            'html_body' => '<p>This is an authorized NeoGiga transactional transport test.</p>',
            'marketplace_id' => $marketplaceId,
        ]);
    }
}
