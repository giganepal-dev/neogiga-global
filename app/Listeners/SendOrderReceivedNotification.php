<?php

namespace App\Listeners;

use App\Events\SellerOrderReceived;
use App\Jobs\SendSellerNotification;
use App\Models\SellerNotification;
use Illuminate\Support\Facades\DB;

class SendOrderReceivedNotification
{
    public function __construct() {}

    public function handle(SellerOrderReceived $event): void
    {
        DB::transaction(function () use ($event) {
            $order = $event->order;
            
            // Calculate dispatch deadline (usually 2 business days)
            $dispatchDeadline = now()->addBusinessDays(2);
            
            $notification = SellerNotification::create([
                'user_id' => $order->vendor->user_id,
                'type' => 'new_order_received',
                'title' => 'New Order Received',
                'message' => "You have received a new order #{$order->order_number}. Please process it by {$dispatchDeadline->format('M d')}.",
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'created_at' => $order->created_at?->toIso8601String(),
                    'total_amount' => $order->total_amount ?? 0,
                    'currency' => $order->currency ?? 'USD',
                    'item_count' => $order->items ? $order->items->count() : 0,
                    'customer_name' => $order->customer_name ?? 'Customer',
                    'shipping_address' => $order->shipping_address ?? '',
                    'dispatch_deadline' => $dispatchDeadline->toIso8601String(),
                ],
            ]);

            SendSellerNotification::dispatch($notification, true);
        });
    }
}
