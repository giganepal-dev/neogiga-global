<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\Cart;
use App\Models\Address;
use App\Services\Pricing\PricingEngineService;
use App\Services\Shipping\ShippingCalculatorService;
use App\Services\Payment\PaymentGatewayRegistry;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Accounting\LedgerService;
use App\Exceptions\CheckoutException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Unified Checkout Orchestrator
 * 
 * Coordinates the entire checkout process atomically:
 * 1. Validates Cart
 * 2. Reserves Inventory
 * 3. Calculates Final Landed Cost (Tax, Duty, Shipping, Discounts)
 * 4. Selects Payment Gateway
 * 5. Creates Order
 * 6. Releases Inventory on failure
 */
class CheckoutOrchestrator
{
    public function __construct(
        private PricingEngineService $pricingEngine,
        private ShippingCalculatorService $shippingCalculator,
        private PaymentGatewayRegistry $paymentRegistry,
        private InventoryReservationService $inventoryResolver,
        private LedgerService $ledgerService
    ) {}

    /**
     * Execute the full checkout flow
     * 
     * @throws CheckoutException
     */
    public function process(Cart $cart, Address $shippingAddress, string $paymentMethod): Order
    {
        return DB::transaction(function () use ($cart, $shippingAddress, $paymentMethod) {
            Log::info("Checkout started for cart #{$cart->id}", ['user' => $cart->user_id]);

            // 1. Validate Stock & Reserve
            $reservation = $this->inventoryResolver->reserve($cart, $shippingAddress->warehouse_id);
            if (!$reservation->success) {
                throw new CheckoutException("Stock reservation failed: " . $reservation->message);
            }

            // 2. Calculate Final Price (Landed Cost)
            $pricingContext = $this->pricingEngine->calculateCheckout($cart, $shippingAddress);
            
            // 3. Calculate Shipping & Tax
            $shippingQuote = $this->shippingCalculator->getQuote($cart, $shippingAddress);
            $finalTotals = $pricingContext->addShippingAndTax($shippingQuote);

            // 4. Select Payment Gateway
            $gateway = $this->paymentRegistry->getGateway($paymentMethod, $shippingAddress->country_code);
            if (!$gateway || !$gateway->isActive()) {
                throw new CheckoutException("Selected payment method unavailable for this region.");
            }

            // 5. Create Order Snapshot
            $order = Order::create([
                'user_id' => $cart->user_id,
                'marketplace_id' => $shippingAddress->marketplace_id,
                'status' => 'pending_payment',
                'currency' => $finalTotals->currency,
                'subtotal' => $finalTotals->subtotal,
                'tax_amount' => $finalTotals->tax,
                'duty_amount' => $finalTotals->duty,
                'shipping_amount' => $finalTotals->shipping,
                'discount_amount' => $finalTotals->discount,
                'total_amount' => $finalTotals->grand_total,
                'shipping_address_id' => $shippingAddress->id,
                'payment_gateway' => $gateway->identifier,
                'snapshot_data' => $finalTotals->toArray(), // Immutable snapshot
            ]);

            // 6. Attach Lines & Accounting Ledger
            foreach ($cart->items as $item) {
                $order->items()->create($item->toArray());
                // Record initial ledger entry (Pending)
                $this->ledgerService->recordOrderLine($order, $item);
            }

            // 7. Initiate Payment Intent
            $paymentIntent = $gateway->createPayment($order, $finalTotals->grand_total);
            $order->update([
                'payment_intent_id' => $paymentIntent->id,
                'payment_url' => $paymentIntent->url,
            ]);

            Log::info("Checkout successful. Order #{$order->id} created. Payment URL generated.");

            return $order->fresh();
        }, 5); // Retry deadlocks 5 times
    }
}
