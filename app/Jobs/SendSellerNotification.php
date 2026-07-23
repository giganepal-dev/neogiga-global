<?php

namespace App\Jobs;

use App\Models\SellerNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSellerNotification extends ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification;
    public $forceEmail;

    public function __construct(SellerNotification $notification, bool $forceEmail = false)
    {
        $this->notification = $notification;
        $this->forceEmail = $forceEmail;
    }

    public function handle(): void
    {
        // Mark as processing
        $this->notification->update(['read_at' => now()]); // Temporary mark to prevent race conditions if needed

        // Always create in-app notification (already exists in DB usually, but ensure delivery status)
        $this->notification->update(['delivered_at' => now()]);

        // Send email if configured or forced
        if ($this->forceEmail || $this->notification->user->prefersEmailNotification($this->notification->type)) {
            $this->sendEmail();
        }
    }

    protected function sendEmail(): void
    {
        $user = $this->notification->user;
        $template = $this->getTemplateClass();

        if ($template && $user->email) {
            try {
                Mail::to($user->email)->send(new $template($this->notification));
            } catch (\Exception $e) {
                \Log::error('Failed to send seller notification email', [
                    'notification_id' => $this->notification->id,
                    'error' => $e->getMessage()
                ]);
                $this->notification->update(['email_failed' => true]);
            }
        }
    }

    protected function getTemplateClass(): ?string
    {
        $mapping = [
            'onboarding_step_completed' => \App\Mail\SellerOnboardingStepCompleted::class,
            'onboarding_correction_required' => \App\Mail\SellerOnboardingCorrectionRequired::class,
            'marketplace_approved' => \App\Mail\SellerMarketplaceApproved::class,
            'marketplace_rejected' => \App\Mail\SellerMarketplaceRejected::class,
            'offer_approved' => \App\Mail\SellerOfferApproved::class,
            'offer_rejected' => \App\Mail\SellerOfferRejected::class,
            'new_order_received' => \App\Mail\SellerNewOrderReceived::class,
            'low_stock_alert' => \App\Mail\SellerLowStockAlert::class,
            'payout_processed' => \App\Mail\SellerPayoutProcessed::class,
            'support_ticket_reply' => \App\Mail\SellerSupportTicketReply::class,
        ];

        return $mapping[$this->notification->type] ?? null;
    }
}
