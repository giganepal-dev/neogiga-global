<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class OrderNotificationService
{
    public function __construct(private TransactionalCommunicationService $communications) {}

    public function orderPlaced(string $email, object $order, ?string $customerName = null): int
    {
        return $this->communications->queue('order_placed', $email, $this->data($order, ['customer_name' => $customerName, 'event_id' => 'placed']));
    }

    public function orderStatus(string $email, string $orderNumber, string $status, ?int $orderId = null, ?int $marketplaceId = null): int
    {
        $event = match ($status) {
            'confirmed' => 'order_confirmed', 'processing' => 'order_processing', 'shipped' => 'order_shipped', 'delivered' => 'order_delivered', 'cancelled' => 'order_cancelled', 'refunded' => 'refund_completed', default => 'support_updated'
        };

        return $this->communications->queue($event, $email, array_filter([
            'order_number' => $orderNumber,
            'order_status' => $status,
            'status' => $status,
            'related_type' => 'order',
            'related_id' => $orderId,
            'marketplace_id' => $marketplaceId,
            // Deterministic per order+status+recipient — dedupes replayed
            // admin clicks/imports via the queue's idempotency key.
            'event_id' => 'status-'.$status,
        ] + $this->orderDetails($orderId), fn ($value) => $value !== null && $value !== ''));
    }

    /** Real order lines/amounts for the customer email; empty when unavailable. */
    private function orderDetails(?int $orderId): array
    {
        if (! $orderId) {
            return [];
        }
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (! $order) {
            return [];
        }
        $products = DB::table('order_items')->where('order_id', $orderId)
            ->limit(20)->get()
            ->map(fn ($item) => trim(($item->product_name ?? $item->name ?? 'Item').' × '.(int) ($item->quantity ?? 1)))
            ->all();

        return array_filter([
            'customer_name' => json_decode((string) ($order->metadata ?? '{}'), true)['customer_name'] ?? null,
            'order_total' => $order->total_amount ?? $order->total ?? null,
            'currency' => $order->currency ?? $order->currency_code ?? null,
            'payment_status' => $order->payment_status ?? null,
            'products' => $products ?: null,
            'tracking_url' => rtrim((string) config('app.url'), '/').'/en/account/orders',
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function tracking(string $email, object $order): int
    {
        return $this->communications->queue('tracking_available', $email, $this->data($order, ['tracking_number' => $order->tracking_number ?? null, 'event_id' => $order->tracking_number ?? 'tracking']));
    }

    public function recipient(object $order): ?string
    {
        $metadata = json_decode((string) ($order->metadata ?? '{}'), true) ?: [];
        $email = $metadata['customer_email'] ?? null;
        if (! $email && ($order->user_id ?? null)) {
            $email = DB::table('users')->where('id', $order->user_id)->value('email');
        }

        return $email && filter_var($email, FILTER_VALIDATE_EMAIL) ? mb_strtolower($email) : null;
    }

    private function data(object $order, array $extra = []): array
    {
        return array_filter([
            'order_number' => $order->order_number ?? null, 'order_status' => $order->status ?? null,
            'related_type' => 'order', 'related_id' => $order->id ?? null, 'marketplace_id' => $order->marketplace_id ?? null,
        ] + $extra, fn ($value) => $value !== null && $value !== '');
    }
}
