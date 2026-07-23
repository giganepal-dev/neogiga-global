<?php

namespace App\Events;

use App\Models\SellerOffer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SellerOfferApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $offer;

    public function __construct(SellerOffer $offer)
    {
        $this->offer = $offer;
    }
}
