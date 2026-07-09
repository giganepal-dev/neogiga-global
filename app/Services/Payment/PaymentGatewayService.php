<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    protected $stripeKey;
    protected $stripeSecret;
    protected $paypalClientId;
    protected $paypalSecret;

    public function __construct()
    {
        $this->stripeKey = config('services.stripe.key');
        $this->stripeSecret = config('services.stripe.secret');
        $this->paypalClientId = config('services.paypal.client_id');
        $this->paypalSecret = config('services.paypal.secret');
    }

    /**
     * Process payment via Stripe
     */
    public function processStripePayment($order, $paymentMethodId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->stripeSecret}",
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => (int)($order->total_amount * 100), // cents
                'currency' => strtolower($order->currency ?? 'usd'),
                'payment_method' => $paymentMethodId,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Update order status
                $order->update([
                    'payment_status' => 'paid',
                    'transaction_id' => $data['id'],
                    'paid_at' => now()
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $data['id'],
                    'status' => $data['status'],
                    'client_secret' => $data['client_secret'] ?? null
                ];
            }

            Log::error('Stripe payment failed', ['error' => $response->body()]);
            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Payment failed'
            ];

        } catch (\Exception $e) {
            Log::error('Stripe payment error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Payment processing error'
            ];
        }
    }

    /**
     * Create Stripe checkout session
     */
    public function createStripeCheckoutSession($order)
    {
        try {
            $lineItems = $order->items->map(fn($item) => [
                'price_data' => [
                    'currency' => strtolower($order->currency ?? 'usd'),
                    'product_data' => [
                        'name' => $item->product->name,
                        'description' => substr($item->product->description, 0, 255)
                    ],
                    'unit_amount' => (int)($item->price * 100)
                ],
                'quantity' => $item->quantity
            ])->toArray();

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->stripeSecret}",
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post('https://api.stripe.com/v1/checkout/sessions', [
                'payment_method_types[]' => 'card',
                'line_items' => json_encode($lineItems),
                'mode' => 'payment',
                'success_url' => route('orders.success', ['order' => $order->id]),
                'cancel_url' => route('orders.cancel'),
                'metadata' => [
                    'order_id' => $order->id
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'session_id' => $data['id'],
                    'url' => $data['url']
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create checkout session'
            ];

        } catch (\Exception $e) {
            Log::error('Stripe checkout error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Checkout error'
            ];
        }
    }

    /**
     * Process payment via PayPal
     */
    public function processPayPalPayment($order)
    {
        try {
            // Get access token
            $tokenResponse = Http::withBasicAuth($this->paypalClientId, $this->paypalSecret)
                ->asForm()
                ->post('https://api-m.sandbox.paypal.com/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if (!$tokenResponse->successful()) {
                return ['success' => false, 'error' => 'PayPal authentication failed'];
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // Create order
            $orderResponse = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post('https://api-m.sandbox.paypal.com/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => strtoupper($order->currency ?? 'USD'),
                        'value' => number_format($order->total_amount, 2, '.', '')
                    ],
                    'description' => "Order #{$order->order_number}"
                ]],
                'application_context' => [
                    'return_url' => route('orders.paypal.success', ['order' => $order->id]),
                    'cancel_url' => route('orders.paypal.cancel')
                ]
            ]);

            if ($orderResponse->successful()) {
                $data = $orderResponse->json();
                return [
                    'success' => true,
                    'order_id' => $data['id'],
                    'approval_url' => collect($data['links'])->firstWhere('rel', 'approve')['href'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => 'PayPal order creation failed'
            ];

        } catch (\Exception $e) {
            Log::error('PayPal payment error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'PayPal processing error'
            ];
        }
    }

    /**
     * Capture PayPal payment
     */
    public function capturePayPalPayment($paypalOrderId, $order)
    {
        try {
            // Get access token
            $tokenResponse = Http::withBasicAuth($this->paypalClientId, $this->paypalSecret)
                ->asForm()
                ->post('https://api-m.sandbox.paypal.com/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if (!$tokenResponse->successful()) {
                return ['success' => false, 'error' => 'PayPal authentication failed'];
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // Capture payment
            $captureResponse = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ])->post("https://api-m.sandbox.paypal.com/v2/checkout/orders/{$paypalOrderId}/capture");

            if ($captureResponse->successful()) {
                $data = $captureResponse->json();
                
                $order->update([
                    'payment_status' => 'paid',
                    'transaction_id' => $data['id'],
                    'paid_at' => now()
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $data['id'],
                    'status' => $data['status'] ?? 'COMPLETED'
                ];
            }

            return [
                'success' => false,
                'error' => 'PayPal capture failed'
            ];

        } catch (\Exception $e) {
            Log::error('PayPal capture error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Capture error'
            ];
        }
    }

    /**
     * Process refund
     */
    public function processRefund($transactionId, $amount, $gateway = 'stripe')
    {
        try {
            if ($gateway === 'stripe') {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->stripeSecret}",
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])->post("https://api.stripe.com/v1/refunds", [
                    'payment_intent' => $transactionId,
                    'amount' => (int)($amount * 100)
                ]);

                if ($response->successful()) {
                    return ['success' => true, 'refund_id' => $response->json()['id']];
                }

                return ['success' => false, 'error' => 'Refund failed'];
            }

            // PayPal refund logic here
            return ['success' => false, 'error' => 'Unsupported gateway'];

        } catch (\Exception $e) {
            Log::error('Refund error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Refund processing error'];
        }
    }
}
