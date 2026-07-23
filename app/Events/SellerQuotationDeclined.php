<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SellerQuotationDeclined
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quotation;

    public function __construct($quotation)
    {
        $this->quotation = $quotation;
    }
}
