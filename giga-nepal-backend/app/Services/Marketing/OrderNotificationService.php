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

        return $this->communications->queue($event, $email, ['order_number' => $orderNumber, 'order_status' => $status, 'status' => $status, 'related_type' => 'order', 'related_id' => $orderId, 'marketplace_id' => $marketplaceId]);
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
