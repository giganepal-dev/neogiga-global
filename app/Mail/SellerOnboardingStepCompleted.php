<?php

namespace App\Mail;

use App\Models\SellerNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerOnboardingStepCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public $notification;
    public $stepName;

    public function __construct(SellerNotification $notification)
    {
        $this->notification = $notification;
        $this->stepName = $notification->data['step_name'] ?? 'Unknown Step';
    }

    public function build(): self
    {
        return $this->subject('NeoGiga - Onboarding Step Completed: ' . $this->stepName)
                    ->view('emails.seller.onboarding_step_completed')
                    ->with([
                        'stepName' => $this->stepName,
                        'sellerName' => $this->notification->user->name,
                        'nextSteps' => $this->getNextSteps(),
                    ]);
    }

    protected function getNextSteps(): array
    {
        $allSteps = [
            'business_profile',
            'legal_documents', 
            'tax_registration',
            'authorized_representative',
            'bank_account',
            'warehouse',
            'marketplace_application',
            'compliance_declaration',
            'seller_agreement',
            'admin_verification',
        ];

        $completedIndex = array_search($this->notification->data['step_key'] ?? '', $allSteps) ?? -1;
        return array_slice($allSteps, $completedIndex + 1, 3);
    }
}
