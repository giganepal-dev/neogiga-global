<?php

namespace App\Events;

use App\Models\VendorOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SellerOrderReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(VendorOrder $order)
    {
        $this->order = $order;
    }
}
