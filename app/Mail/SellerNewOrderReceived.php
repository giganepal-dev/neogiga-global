<?php

namespace App\Mail;

use App\Models\SellerNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerNewOrderReceived extends Mailable
{
    use Queueable, SerializesModels;

    public $notification;
    public $order;

    public function __construct(SellerNotification $notification)
    {
        $this->notification = $notification;
        $this->order = $notification->data['order'] ?? null;
    }

    public function build(): self
    {
        return $this->subject('NeoGiga - New Order Received #' . ($this->order['order_number'] ?? 'Unknown'))
                    ->view('emails.seller.new_order_received')
                    ->with([
                        'orderNumber' => $this->order['order_number'] ?? 'Unknown',
                        'orderDate' => $this->order['created_at'] ?? now(),
                        'totalAmount' => $this->order['total_amount'] ?? 0,
                        'currency' => $this->order['currency'] ?? 'USD',
                        'itemCount' => $this->order['item_count'] ?? 0,
                        'customerName' => $this->order['customer_name'] ?? 'Customer',
                        'shippingAddress' => $this->order['shipping_address'] ?? '',
                        'dispatchDeadline' => $this->order['dispatch_deadline'] ?? now()->addDays(2),
                    ]);
    }
}
