<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\PaymentGateways\PaymentGatewayFactory;
use App\Services\PaymentGateways\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentCallbackController extends Controller
{
    /**
     * Handle payment gateway callback
     *
     * @param Request $request
     * @param string $gateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleCallback(Request $request, string $gateway)
    {
        try {
            $gatewayService = PaymentGatewayFactory::get($gateway);
            
            // Extract transaction ID based on gateway
            $transactionId = $this->extractTransactionId($request, $gateway);
            
            if (!$transactionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction ID not found',
                ], 400);
            }

            // Verify payment with gateway
            $verification = $gatewayService->verify($transactionId);

            if ($verification['success']) {
                // Update order status in database
                DB::beginTransaction();
                
                try {
                    $order = DB::table('orders')
                        ->where('order_number', $transactionId)
                        ->orWhere('id', function($query) use ($transactionId) {
                            $query->select('id')
                                  ->from('orders')
                                  ->where('payment_transaction_id', $transactionId);
                        })
                        ->first();

                    if ($order) {
                        // Update order status
                        DB::table('orders')
                            ->where('id', $order->id)
                            ->update([
                                'payment_status' => 'paid',
                                'payment_verified_at' => now(),
                                'payment_transaction_id' => $transactionId,
                                'payment_gateway_response' => json_encode($verification['gateway_response'] ?? []),
                                'status' => 'confirmed',
                                'updated_at' => now(),
                            ]);

                        // Release inventory reservation
                        DB::table('inventory_reservations')
                            ->where('order_id', $order->id)
                            ->where('status', 'reserved')
                            ->update([
                                'status' => 'converted',
                                'converted_at' => now(),
                                'updated_at' => now(),
                            ]);

                        DB::commit();

                        return response()->json([
                            'success' => true,
                            'message' => 'Payment verified successfully',
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'amount' => $verification['amount'] ?? 0,
                        ]);
                    }

                    DB::rollBack();
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found',
                    ], 404);

                } catch (Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'status' => $verification['status'] ?? 'unknown',
            ]);

        } catch (Exception $e) {
            Log::error('Payment callback error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Payment callback processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle payment failure
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleFailure(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            
            if ($orderId) {
                // Release inventory reservation
                DB::table('inventory_reservations')
                    ->where('order_id', $orderId)
                    ->where('status', 'reserved')
                    ->update([
                        'status' => 'released',
                        'released_at' => now(),
                        'release_reason' => 'payment_failed',
                        'updated_at' => now(),
                    ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment failed',
            ]);

        } catch (Exception $e) {
            Log::error('Payment failure handling error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment failure',
            ], 500);
        }
    }

    /**
     * Extract transaction ID from request based on gateway
     *
     * @param Request $request
     * @param string $gateway
     * @return string|null
     */
    protected function extractTransactionId(Request $request, string $gateway): ?string
    {
        return match (strtolower($gateway)) {
            'esewa' => $request->input('transaction_uuid') ?? $request->input('transaction_id'),
            'khalti' => $request->input('pidx') ?? $request->input('transaction_id'),
            'stripe' => $request->input('payment_intent') ?? $request->input('transaction_id'),
            'cod' => $request->input('order_id') ?? $request->input('transaction_id'),
            default => $request->input('transaction_id'),
        };
    }
}
