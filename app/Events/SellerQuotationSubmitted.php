<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SellerQuotationSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quotation;
    public $rfq;

    public function __construct($quotation, $rfq)
    {
        $this->quotation = $quotation;
        $this->rfq = $rfq;
    }
}
