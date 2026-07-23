<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SellerOrderRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $sellerId;
    public $reason;

    public function __construct($order, $sellerId, $reason)
    {
        $this->order = $order;
        $this->sellerId = $sellerId;
        $this->reason = $reason;
    }
}
