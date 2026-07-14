<?php

namespace App\Services\Email;

/**
 * Interface for email delivery providers.
 * 
 * All email providers must implement this interface to work with the
 * unified EmailDeliveryManager.
 */
interface EmailProviderInterface
{
    /**
     * Provider name identifier (e.g., 'resend', 'ses', 'smtp').
     */
    public function name(): string;

    /**
     * Send an email message.
     * 
     * @param array $to Recipient(s) with email and optional name
     * @param string $subject Email subject
     * @param string $bodyHtml HTML body content
     * @param string|null $bodyText Plain text body (optional)
     * @param array $options Additional options (from_email, from_name, reply_to, attachments, etc.)
     * @return array Result with success status, message_id, and provider response
     */
    public function send(array $to, string $subject, string $bodyHtml, ?string $bodyText = null, array $options = []): array;

    /**
     * Test the provider connection/configuration.
     * 
     * @param string $testRecipient Email address to send test message to
     * @return array Result with success status and message
     */
    public function test(string $testRecipient): array;

    /**
     * Get provider configuration status.
     * 
     * @return array Status information including is_configured, is_active, last_error
     */
    public function getStatus(): array;
}
