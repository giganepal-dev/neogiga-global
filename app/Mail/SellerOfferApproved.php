<?php

namespace App\Mail;

use App\Models\SellerNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerOfferApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $notification;
    public $offer;

    public function __construct(SellerNotification $notification)
    {
        $this->notification = $notification;
        $this->offer = $notification->data['offer'] ?? null;
    }

    public function build(): self
    {
        return $this->subject('NeoGiga - Offer Approved')
                    ->view('emails.seller.offer_approved')
                    ->with([
                        'productName' => $this->offer['product_name'] ?? 'Product',
                        'mpn' => $this->offer['mpn'] ?? '',
                        'price' => $this->offer['price'] ?? 0,
                        'currency' => $this->offer['currency'] ?? 'USD',
                        'quantity' => $this->offer['quantity'] ?? 0,
                        'marketplace' => $this->offer['marketplace_name'] ?? 'All Marketplaces',
                        'approvedAt' => $this->offer['approved_at'] ?? now(),
                    ]);
    }
}
