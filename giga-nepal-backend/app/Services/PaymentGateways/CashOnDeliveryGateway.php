<?php

namespace App\Services\PaymentGateways;

use Illuminate\Support\Facades\Log;
use Exception;

class CashOnDeliveryGateway implements PaymentGatewayInterface
{
    public function initiate(array $data): array
    {
        try {
            // COD doesn't require external API calls
            // Just return success with order details
            return [
                'success' => true,
                'gateway' => 'cod',
                'transaction_id' => $data['order_id'],
                'status' => 'pending',
                'message' => 'Cash on Delivery selected. Please pay upon delivery.',
                'data' => [
                    'order_id' => $data['order_id'],
                    'amount' => $data['amount'],
                    'customer_name' => $data['customer_name'] ?? '',
                    'customer_phone' => $data['customer_phone'] ?? '',
                    'delivery_address' => $data['delivery_address'] ?? '',
                ],
            ];
        } catch (Exception $e) {
            Log::error('COD payment initiation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment initiation failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function verify(string $transactionId): array
    {
        // COD verification happens on delivery confirmation
        return [
            'success' => false,
            'status' => 'pending',
            'message' => 'COD payment will be verified upon delivery',
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        // COD refunds are handled manually or through bank transfer
        return [
            'success' => false,
            'error' => 'COD refunds must be processed manually',
        ];
    }

    public function getName(): string
    {
        return 'Cash on Delivery';
    }
}
