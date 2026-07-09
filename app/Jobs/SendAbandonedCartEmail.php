<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $cart;
    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $cart)
    {
        $this->user = $user;
        $this->cart = $cart;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send abandoned cart recovery email
        Mail::raw(
            "Hi {$this->user->name},\n\nYou left items in your cart!\n\nComplete your purchase now.",
            fn($message) => $message
                ->to($this->user->email)
                ->subject('Complete Your Purchase - NeoGIGA')
        );
    }
}
