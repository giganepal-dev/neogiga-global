<?php

namespace App\Services\PaymentGateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class EsewaGateway implements PaymentGatewayInterface
{
    protected string $endpoint;
    protected string $secretKey;
    protected string $merchantCode;

    public function __construct()
    {
        $this->endpoint = config('services.esewa.endpoint', 'https://rc-processor.esewa.com.np/api/');
        $this->secretKey = config('services.esewa.secret_key');
        $this->merchantCode = config('services.esewa.merchant_code');
    }

    public function initiate(array $data): array
    {
        try {
            $payload = [
                'amount' => number_format($data['amount'], 2, '.', ''),
                'transaction_uuid' => $data['order_id'],
                'product_code' => $this->merchantCode,
                'product_service_charge' => 0,
                'product_delivery_charge' => 0,
                'success_url' => route('payment.callback', ['gateway' => 'esewa']),
                'failure_url' => route('payment.failure'),
                'signed_field_names' => 'total_amount,transaction_uuid,product_code',
            ];

            // Generate signature
            $payload['signature'] = $this->generateSignature($payload);

            return [
                'success' => true,
                'gateway' => 'esewa',
                'redirect_url' => $this->endpoint . 'epay/main',
                'method' => 'POST',
                'data' => $payload,
                'transaction_id' => $data['order_id'],
            ];
        } catch (Exception $e) {
            Log::error('eSewa payment initiation failed: ' . $e->getMessage());
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
                'Content-Type' => 'application/json',
            ])->get($this->endpoint . 'epay/transaction/status/', [
                'product_code' => $this->merchantCode,
                'transaction_id' => $transactionId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'COMPLETE') {
                    return [
                        'success' => true,
                        'status' => 'completed',
                        'transaction_id' => $transactionId,
                        'amount' => $data['total_amount'] ?? 0,
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
            Log::error('eSewa payment verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment verification failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function refund(string $transactionId, float $amount): array
    {
        // eSewa refund implementation (if supported)
        return [
            'success' => false,
            'error' => 'Refund not implemented for eSewa',
        ];
    }

    public function getName(): string
    {
        return 'eSewa';
    }

    protected function generateSignature(array $data): string
    {
        $fields = explode(',', $data['signed_field_names']);
        $string = '';
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $string .= $field . '=' . $data[$field] . '&';
            }
        }
        
        $string = rtrim($string, '&');
        return hash_hmac('sha256', $string, $this->secretKey);
    }
}
