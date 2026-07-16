<?php

namespace App\Services\Payments\Contracts;

/**
 * Payment Gateway Contract
 * 
 * All payment gateway implementations must implement this interface.
 */
interface PaymentGateway
{
    /**
     * Create a new payment
     * 
     * @param array $data Payment data including amount, currency, order info
     * @return array Result with success status and payment details
     */
    public function createPayment(array $data): array;

    /**
     * Confirm a pending payment
     * 
     * @param string $paymentIntentId Payment intent identifier
     * @return array Confirmation result
     */
    public function confirmPayment(string $paymentIntentId): array;

    /**
     * Get the status of a payment
     * 
     * @param string $paymentIntentId Payment intent identifier
     * @return array Payment status information
     */
    public function getPaymentStatus(string $paymentIntentId): array;

    /**
     * Process a refund
     * 
     * @param string $paymentIntentId Original payment identifier
     * @param float|null $amount Refund amount (null for full refund)
     * @param string|null $reason Reason for refund
     * @return array Refund result
     */
    public function refund(string $paymentIntentId, ?float $amount = null, ?string $reason = null): array;

    /**
     * Verify webhook signature
     * 
     * @param string $payload Raw webhook payload
     * @param string $signature Webhook signature header
     * @param string $tolerance Time tolerance in seconds
     * @return bool True if signature is valid
     */
    public function verifyWebhook(string $payload, string $signature, string $tolerance = 300): bool;

    /**
     * Handle incoming webhook event
     * 
     * @param array $event Webhook event data
     * @return array Handling result
     */
    public function handleWebhook(array $event): array;

    /**
     * Get the gateway name
     * 
     * @return string Gateway identifier
     */
    public function getName(): string;

    /**
     * Check if the gateway is available/configured
     * 
     * @return bool True if gateway is ready to use
     */
    public function isAvailable(): bool;

    /**
     * Get supported payment methods
     * 
     * @return array List of supported payment method types
     */
    public function getSupportedMethods(): array;
}
