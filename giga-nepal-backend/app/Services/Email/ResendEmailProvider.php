<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Resend Email Provider
 * 
 * Integrates with Resend.com email API for transactional and marketing emails.
 * 
 * @see https://resend.com/features/email-api
 */
class ResendEmailProvider implements EmailProviderInterface
{
    private const API_BASE = 'https://api.resend.com';

    private ?string $apiKey = null;
    private ?string $fromEmail = null;
    private ?string $fromName = null;
    private bool $isActive = false;

    public function __construct()
    {
        $this->apiKey = config('services.resend.api_key');
        $this->fromEmail = config('services.resend.from_address');
        $this->fromName = config('services.resend.from_name', 'NeoGiga');
        $this->isActive = !empty($this->apiKey) && !empty($this->fromEmail);
    }

    public function name(): string
    {
        return 'resend';
    }

    /**
     * Send email via Resend API
     */
    public function send(array $to, string $subject, string $bodyHtml, ?string $bodyText = null, array $options = []): array
    {
        if (!$this->isActive) {
            return [
                'success' => false,
                'error' => 'Resend provider is not configured',
                'provider' => $this->name(),
            ];
        }

        try {
            $payload = [
                'from' => sprintf(
                    '%s <%s>',
                    $options['from_name'] ?? $this->fromName,
                    $options['from_email'] ?? $this->fromEmail
                ),
                'to' => $this->formatRecipients($to),
                'subject' => $subject,
                'html' => $bodyHtml,
            ];

            if ($bodyText) {
                $payload['text'] = $bodyText;
            }

            // Optional fields
            if (!empty($options['reply_to'])) {
                $payload['reply_to'] = $options['reply_to'];
            }

            if (!empty($options['tags'])) {
                $payload['tags'] = $options['tags'];
            }

            if (!empty($options['headers'])) {
                $payload['headers'] = $options['headers'];
            }

            // Attachments (base64 encoded)
            if (!empty($options['attachments'])) {
                $payload['attachments'] = $options['attachments'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(self::API_BASE . '/emails', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Resend email sent successfully', [
                    'message_id' => $data['id'] ?? null,
                    'to' => $this->formatRecipientsForLog($to),
                    'subject' => $subject,
                ]);

                return [
                    'success' => true,
                    'message_id' => $data['id'] ?? null,
                    'provider' => $this->name(),
                    'response' => $data,
                ];
            }

            $errorData = $response->json();
            
            Log::warning('Resend email failed', [
                'status' => $response->status(),
                'error' => $errorData['message'] ?? 'Unknown error',
                'to' => $this->formatRecipientsForLog($to),
                'subject' => $subject,
            ]);

            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'Resend API error',
                'provider' => $this->name(),
                'response' => $errorData,
            ];

        } catch (Exception $e) {
            Log::error('Resend email exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'to' => $this->formatRecipientsForLog($to),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $this->name(),
            ];
        }
    }

    /**
     * Test Resend configuration
     */
    public function test(string $testRecipient): array
    {
        if (!$this->isActive) {
            return [
                'success' => false,
                'message' => 'Resend is not configured. Please set RESEND_API_KEY and RESEND_FROM_ADDRESS.',
            ];
        }

        $result = $this->send(
            [$testRecipient],
            'NeoGiga Email Test',
            '<h1>Test Successful</h1><p>This is a test email from NeoGiga using Resend.</p>',
            'This is a test email from NeoGiga using Resend.',
            ['tags' => [['name' => 'type', 'value' => 'test']]]
        );

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Test email sent successfully to ' . $testRecipient,
                'message_id' => $result['message_id'],
            ];
        }

        return [
            'success' => false,
            'message' => 'Test failed: ' . ($result['error'] ?? 'Unknown error'),
        ];
    }

    /**
     * Get provider status
     */
    public function getStatus(): array
    {
        return [
            'name' => $this->name(),
            'display_name' => 'Resend',
            'is_configured' => !empty($this->apiKey) && !empty($this->fromEmail),
            'is_active' => $this->isActive,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName,
            'last_error' => null, // Would need to fetch from logs
        ];
    }

    /**
     * Format recipients for Resend API
     */
    private function formatRecipients(array $to): array
    {
        $formatted = [];
        
        foreach ($to as $recipient) {
            if (is_string($recipient)) {
                $formatted[] = $recipient;
            } elseif (is_array($recipient)) {
                if (!empty($recipient['name'])) {
                    $formatted[] = sprintf('%s <%s>', $recipient['name'], $recipient['email']);
                } else {
                    $formatted[] = $recipient['email'];
                }
            }
        }

        return $formatted;
    }

    /**
     * Format recipients for logging (hide full emails if needed)
     */
    private function formatRecipientsForLog(array $to): string
    {
        $emails = [];
        
        foreach ($to as $recipient) {
            if (is_string($recipient)) {
                $emails[] = $recipient;
            } elseif (is_array($recipient) && !empty($recipient['email'])) {
                $emails[] = $recipient['email'];
            }
        }

        return implode(', ', $emails);
    }
}
