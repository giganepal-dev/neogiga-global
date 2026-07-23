<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \App\Events\SellerOfferApproved::class => [
            \App\Listeners\SendOfferApprovedNotification::class,
        ],
        \App\Events\SellerOrderReceived::class => [
            \App\Listeners\SendOrderReceivedNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
