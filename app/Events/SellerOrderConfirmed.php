<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SellerOrderConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $sellerId;

    public function __construct($order, $sellerId)
    {
        $this->order = $order;
        $this->sellerId = $sellerId;
    }
}
