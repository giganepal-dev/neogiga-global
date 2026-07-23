<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SellerOrderShipped
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $shipment;
    public $sellerId;

    public function __construct($order, $shipment, $sellerId)
    {
        $this->order = $order;
        $this->shipment = $shipment;
        $this->sellerId = $sellerId;
    }
}
