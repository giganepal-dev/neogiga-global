<?php

namespace App\Services\PaymentGateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class StripeGateway implements PaymentGatewayInterface
{
    protected string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey = config('services.stripe.secret');
        $this->endpoint = 'https://api.stripe.com/v1/';
    }

    public function initiate(array $data): array
    {
        try {
            // Create Payment Intent
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post($this->endpoint . 'payment_intents', [
                'amount' => (int) ($data['amount'] * 100), // Stripe uses cents
                'currency' => strtolower($data['currency'] ?? 'usd'),
                'metadata' => [
                    'order_id' => $data['order_id'],
                    'customer_email' => $data['customer_email'] ?? '',
                ],
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                return [
                    'success' => true,
                    'gateway' => 'stripe',
                    'client_secret' => $responseData['client_secret'] ?? '',
                    'payment_intent_id' => $responseData['id'] ?? '',
                    'transaction_id' => $responseData['id'] ?? '',
                    'data' => $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment initiation failed',
                'message' => $response->body(),
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment initiation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment initiation failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function verify(string $transactionId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->endpoint . 'payment_intents/' . $transactionId);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'succeeded') {
                    return [
                        'success' => true,
                        'status' => 'completed',
                        'transaction_id' => $transactionId,
                        'amount' => ($data['amount'] ?? 0) / 100,
                        'gateway_response' => $data,
                    ];
                }

                return [
                    'success' => false,
                    'status' => 'pending',
                    'transaction_id' => $transactionId,
                ];
            }

            return [
                'success' => false,
                'error' => 'Verification failed',
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment verification failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function refund(string $transactionId, float $amount): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post($this->endpoint . 'refunds', [
                'payment_intent' => $transactionId,
                'amount' => (int) ($amount * 100),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'status' => 'refunded',
                    'transaction_id' => $transactionId,
                    'refund_id' => $data['id'] ?? '',
                ];
            }

            return [
                'success' => false,
                'error' => 'Refund failed',
                'message' => $response->body(),
            ];
        } catch (Exception $e) {
            Log::error('Stripe refund failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Refund failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getName(): string
    {
        return 'Stripe';
    }
}
