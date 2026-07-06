<?php

namespace App\Http\Controllers\Api\Order;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Cart;
use App\Models\Marketplace\InventoryStock;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use ApiResponses;

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirm' => ['accepted'],
            'payment_method' => ['required', 'in:bank_transfer,cod,manual'],
            'billing_address' => ['sometimes', 'array'],
            'shipping_address' => ['sometimes', 'array'],
            'customer_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $cart = Cart::query()
            ->active()
            ->where('user_id', $request->user()->id)
            ->with(['items.product'])
            ->latest()
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return $this->error('Active cart is empty.', 422);
        }

        foreach ($cart->items as $item) {
            if (!$this->hasStock($item->product_id, $cart->marketplace_id, $item->quantity, $item->variant_id)) {
                return $this->error("Insufficient stock for {$item->product?->name}.", 422);
            }
        }

        return DB::transaction(function () use ($cart, $request, $validated) {
            $cart->calculateTotal();
            $cart->refresh()->load(['items.product']);

            $order = Order::create([
                'order_number' => 'NG-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                'user_id' => $request->user()->id,
                'marketplace_id' => $cart->marketplace_id,
                'status' => 'pending',
                'currency_code' => $cart->currency_code,
                'subtotal' => $cart->subtotal,
                'tax_total' => $cart->tax_total,
                'discount_total' => $cart->discount_total,
                'shipping_total' => $cart->shipping_total,
                'grand_total' => $cart->grand_total,
                'amount_paid' => 0,
                'amount_due' => $cart->grand_total,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'billing_address' => $validated['billing_address'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'customer_notes' => $validated['customer_notes'] ?? null,
                'metadata' => [
                    'cart_id' => $cart->id,
                    'phase' => 'phase_1_pending_payment',
                ],
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->variant_id,
                    'product_name' => $item->product?->name ?? 'Unknown product',
                    'product_sku' => $item->product?->sku ?? 'UNKNOWN',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_amount' => $item->tax_amount,
                    'discount_amount' => $item->discount_amount,
                    'total_price' => $item->total,
                    'metadata' => $item->metadata,
                ]);
            }

            Payment::create([
                'payment_number' => 'PAY-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                'order_id' => $order->id,
                'marketplace_id' => $order->marketplace_id,
                'payment_method' => $validated['payment_method'],
                'payment_gateway' => 'manual',
                'amount' => $order->grand_total,
                'currency_code' => $order->currency_code,
                'status' => 'pending',
                'payment_details' => [
                    'requires_manual_confirmation' => true,
                    'provider_call_made' => false,
                ],
            ]);

            $cart->forceFill(['is_active' => false])->save();

            return $this->success($order->fresh()->load(['items', 'payments']), 201);
        });
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(25);

        return $this->success($orders);
    }

    public function show(Request $request, int $order): JsonResponse
    {
        $orderModel = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items', 'payments'])
            ->find($order);

        if (!$orderModel) {
            return $this->error('Order not found.', 404);
        }

        return $this->success($orderModel);
    }

    public function invoice(int $order): JsonResponse
    {
        return $this->notImplemented('Invoice generation', 'Phase 2');
    }

    private function hasStock(int $productId, ?int $marketplaceId, int $quantity, ?int $variantId = null): bool
    {
        $available = InventoryStock::query()
            ->where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('variant_id', $variantId))
            ->when($marketplaceId, fn ($q) => $q->where('marketplace_id', $marketplaceId))
            ->sum('quantity_available');

        return (int) $available >= $quantity;
    }
}
