<?php

namespace App\Services\Payments\Gateways;

use App\Services\Payments\Contracts\PaymentGateway;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Payment Gateway Implementation
 * 
 * Production-ready Stripe integration with:
 * - Payment intent creation
 * - Webhook signature verification
 * - Idempotency support
 * - Refund processing
 * - 3D Secure handling
 */
class StripeGateway implements PaymentGateway
{
    protected string $apiKey;
    protected string $webhookSecret;
    protected string $apiVersion = '2023-10-16';
    protected string $baseUrl = 'https://api.stripe.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.stripe.secret', env('STRIPE_SECRET'));
        $this->webhookSecret = config('services.stripe.webhook_secret', env('STRIPE_WEBHOOK_SECRET'));
    }

    /**
     * Create a payment intent
     */
    public function createPayment(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Stripe-Version' => $this->apiVersion,
                'Idempotency-Key' => $data['idempotency_key'] ?? uniqid('stripe_'),
            ])->asForm()->post("{$this->baseUrl}/payment_intents", [
                'amount' => (int) ($data['amount'] * 100), // Convert to cents
                'currency' => strtolower($data['currency']),
                'payment_method_types' => $data['payment_methods'] ?? ['card'],
                'capture_method' => $data['capture_method'] ?? 'automatic',
                'confirmation_method' => $data['confirmation_method'] ?? 'automatic',
                'metadata' => $data['metadata'] ?? [],
                'description' => $data['description'] ?? 'Order payment',
                'return_url' => $data['return_url'] ?? null,
            ]);

            if ($response->failed()) {
                throw new Exception("Stripe API error: " . $response->body());
            }

            $result = $response->json();

            return [
                'success' => true,
                'payment_intent_id' => $result['id'],
                'client_secret' => $result['client_secret'],
                'status' => $result['status'],
                'requires_action' => in_array($result['status'], ['requires_confirmation', 'requires_action']),
                'next_action' => $result['next_action'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm a payment intent
     */
    public function confirmPayment(string $paymentIntentId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Stripe-Version' => $this->apiVersion,
            ])->asForm()->post("{$this->baseUrl}/payment_intents/{$paymentIntentId}/confirm", []);

            if ($response->failed()) {
                throw new Exception("Stripe confirmation error: " . $response->body());
            }

            $result = $response->json();

            return [
                'success' => true,
                'payment_intent_id' => $result['id'],
                'status' => $result['status'],
                'charge_id' => $result['charges']['data'][0]['id'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment confirmation failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment intent status
     */
    public function getPaymentStatus(string $paymentIntentId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Stripe-Version' => $this->apiVersion,
            ])->get("{$this->baseUrl}/payment_intents/{$paymentIntentId}");

            if ($response->failed()) {
                throw new Exception("Stripe fetch error: " . $response->body());
            }

            $result = $response->json();

            return [
                'success' => true,
                'payment_intent_id' => $result['id'],
                'status' => $result['status'],
                'amount' => $result['amount'] / 100,
                'currency' => $result['currency'],
                'created_at' => date('Y-m-d H:i:s', $result['created']),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process a refund
     */
    public function refund(string $paymentIntentId, float $amount = null, string $reason = null): array
    {
        try {
            $refundData = [
                'payment_intent' => $paymentIntentId,
                'reason' => $reason ?? 'requested_by_customer',
            ];

            if ($amount !== null) {
                $refundData['amount'] = (int) ($amount * 100);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Stripe-Version' => $this->apiVersion,
            ])->asForm()->post("{$this->baseUrl}/refunds", $refundData);

            if ($response->failed()) {
                throw new Exception("Stripe refund error: " . $response->body());
            }

            $result = $response->json();

            return [
                'success' => true,
                'refund_id' => $result['id'],
                'status' => $result['status'],
                'amount' => $result['amount'] / 100,
            ];
        } catch (Exception $e) {
            Log::error('Stripe refund failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(string $payload, string $signature, string $tolerance = 300): bool
    {
        if (empty($this->webhookSecret)) {
            Log::warning('Stripe webhook secret not configured');
            return false;
        }

        try {
            // Extract timestamp and signatures from header
            $header = explode(',', $signature);
            $timestamp = null;
            $signatures = [];

            foreach ($header as $item) {
                $parts = explode('=', $item, 2);
                if ($parts[0] === 't') {
                    $timestamp = (int) $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }

            if (!$timestamp) {
                return false;
            }

            // Check timestamp tolerance
            if (abs(time() - $timestamp) > $tolerance) {
                return false;
            }

            // Create signed payload
            $signedPayload = "{$timestamp}.{$payload}";
            $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

            // Check if any signature matches
            foreach ($signatures as $signature) {
                if (hash_equals($expectedSignature, $signature)) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            Log::error('Stripe webhook verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle webhook event
     */
    public function handleWebhook(array $event): array
    {
        $eventType = $event['type'] ?? '';
        $data = $event['data']['object'] ?? [];

        Log::info('Stripe webhook received', ['type' => $eventType, 'id' => $data['id'] ?? null]);

        switch ($eventType) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSucceeded($data);
            
            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailed($data);
            
            case 'charge.refunded':
                return $this->handleRefunded($data);
            
            default:
                return ['handled' => false, 'message' => 'Event type not handled'];
        }
    }

    protected function handlePaymentSucceeded(array $data): array
    {
        return [
            'handled' => true,
            'action' => 'mark_order_paid',
            'payment_intent_id' => $data['id'],
            'amount' => $data['amount'] / 100,
            'currency' => $data['currency'],
            'transaction_id' => $data['charges']['data'][0]['id'] ?? null,
        ];
    }

    protected function handlePaymentFailed(array $data): array
    {
        return [
            'handled' => true,
            'action' => 'mark_payment_failed',
            'payment_intent_id' => $data['id'],
            'failure_reason' => $data['last_payment_error']['message'] ?? 'Unknown error',
        ];
    }

    protected function handleRefunded(array $data): array
    {
        return [
            'handled' => true,
            'action' => 'mark_refund_completed',
            'charge_id' => $data['id'],
            'refund_id' => $data['refunds']['data'][0]['id'] ?? null,
            'amount' => $data['amount_refunded'] / 100,
        ];
    }

    /**
     * Get gateway name
     */
    public function getName(): string
    {
        return 'stripe';
    }

    /**
     * Check if gateway is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey) && !empty($this->webhookSecret);
    }

    /**
     * Get supported payment methods
     */
    public function getSupportedMethods(): array
    {
        return ['card', 'alipay', 'giropay', 'ideal', 'sepa_debit', 'sofort', 'bancontact', 'eps'];
    }
}
