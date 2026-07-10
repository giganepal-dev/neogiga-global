<?php

namespace App\Services\PaymentGateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class KhaltiGateway implements PaymentGatewayInterface
{
    protected string $endpoint;
    protected string $secretKey;
    protected string $publicKey;

    public function __construct()
    {
        $this->endpoint = config('services.khalti.endpoint', 'https://a.khalti.com/api/v2/');
        $this->secretKey = config('services.khalti.secret_key');
        $this->publicKey = config('services.khalti.public_key');
    }

    public function initiate(array $data): array
    {
        try {
            $payload = [
                'return_url' => route('payment.callback', ['gateway' => 'khalti']),
                'website_url' => config('app.url'),
                'amount' => (int) ($data['amount'] * 100), // Khalti uses paisa
                'purchase_order_id' => $data['order_id'],
                'purchase_order_name' => $data['description'] ?? 'Order Payment',
                'customer_info' => [
                    'name' => $data['customer_name'] ?? 'Customer',
                    'email' => $data['customer_email'] ?? '',
                    'phone' => $data['customer_phone'] ?? '',
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Key ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->endpoint . 'epayment/initiate/', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                return [
                    'success' => true,
                    'gateway' => 'khalti',
                    'redirect_url' => $responseData['payment_url'] ?? '',
                    'method' => 'GET',
                    'transaction_id' => $responseData['pidx'] ?? '',
                    'data' => $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment initiation failed',
                'message' => $response->body(),
            ];
        } catch (Exception $e) {
            Log::error('Khalti payment initiation failed: ' . $e->getMessage());
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
                'Authorization' => 'Key ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->endpoint . 'epayment/lookup/', [
                'pidx' => $transactionId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'Completed') {
                    return [
                        'success' => true,
                        'status' => 'completed',
                        'transaction_id' => $transactionId,
                        'amount' => ($data['amount'] ?? 0) / 100, // Convert paisa to rupees
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
            Log::error('Khalti payment verification failed: ' . $e->getMessage());
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
                'Authorization' => 'Key ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->endpoint . 'epayment/refund/', [
                'pidx' => $transactionId,
                'amount' => (int) ($amount * 100),
                'reason' => 'Refund request',
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => 'refunded',
                    'transaction_id' => $transactionId,
                ];
            }

            return [
                'success' => false,
                'error' => 'Refund failed',
                'message' => $response->body(),
            ];
        } catch (Exception $e) {
            Log::error('Khalti refund failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Refund failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getName(): string
    {
        return 'Khalti';
    }
}
