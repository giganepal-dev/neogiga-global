<?php

namespace App\Listeners;

use App\Events\SellerOfferApproved;
use App\Jobs\SendSellerNotification;
use App\Models\SellerNotification;

class SendOfferApprovedNotification
{
    public function __construct() {}

    public function handle(SellerOfferApproved $event): void
    {
        $offer = $event->offer;
        
        $notification = SellerNotification::create([
            'user_id' => $offer->vendor->user_id,
            'type' => 'offer_approved',
            'title' => 'Offer Approved',
            'message' => "Your offer for {$offer->product->name} has been approved and is now live.",
            'data' => [
                'offer_id' => $offer->id,
                'product_name' => $offer->product->name,
                'mpn' => $offer->product->mpn ?? '',
                'price' => $offer->base_price,
                'currency' => $offer->currency,
                'quantity' => $offer->available_quantity,
                'marketplace_name' => $offer->marketplace ? $offer->marketplace->name : 'All',
                'approved_at' => $offer->approved_at?->toIso8601String(),
            ],
        ]);

        SendSellerNotification::dispatch($notification, true);
    }
}
