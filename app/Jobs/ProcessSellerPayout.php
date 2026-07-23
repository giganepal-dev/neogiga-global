<?php

namespace App\Jobs;

use App\Models\SellerPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSellerPayout extends ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payout;

    public function __construct(SellerPayout $payout)
    {
        $this->payout = $payout;
    }

    public function handle(): void
    {
        DB::transaction(function () {
            try {
                // Verify payout is in correct status
                if ($this->payout->status !== 'approved') {
                    throw new \Exception("Payout must be approved before processing");
                }

                // Update status to processing
                $this->payout->update(['status' => 'processing']);

                // Simulate payment gateway call (would integrate with Stripe/PayPal/etc in production)
                $paymentSuccess = $this->executePayment();

                if ($paymentSuccess) {
                    $this->payout->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    ]);

                    // Create ledger entry
                    \App\Models\VendorLedger::create([
                        'vendor_id' => $this->payout->vendor_id,
                        'type' => 'payout',
                        'amount' => -$this->payout->net_amount,
                        'balance_after' => $this->payout->vendor->ledger_balance - $this->payout->net_amount,
                        'reference_type' => 'payout',
                        'reference_id' => $this->payout->id,
                        'description' => "Payout #{$this->payout->payout_number}",
                    ]);

                    // Update vendor balance
                    $this->payout->vendor->decrement('ledger_balance', $this->payout->net_amount);

                    // Send notification
                    \App\Models\SellerNotification::create([
                        'user_id' => $this->payout->vendor->user_id,
                        'type' => 'payout_processed',
                        'title' => 'Payout Processed',
                        'message' => "Your payout of {$this->payout->currency} {$this->payout->net_amount} has been processed.",
                        'data' => ['payout_id' => $this->payout->id],
                    ]);

                    Log::info('Seller payout processed successfully', [
                        'payout_id' => $this->payout->id,
                        'amount' => $this->payout->net_amount,
                    ]);

                } else {
                    throw new \Exception("Payment gateway returned failure");
                }

            } catch (\Exception $e) {
                Log::error('Seller payout failed', [
                    'payout_id' => $this->payout->id,
                    'error' => $e->getMessage(),
                ]);

                $this->payout->update([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);

                \App\Models\SellerNotification::create([
                    'user_id' => $this->payout->vendor->user_id,
                    'type' => 'payout_failed',
                    'title' => 'Payout Failed',
                    'message' => "Your payout failed: {$e->getMessage()}",
                    'data' => ['payout_id' => $this->payout->id],
                ]);

                throw $e;
            }
        });
    }

    protected function executePayment(): bool
    {
        // In production, integrate with actual payment gateway
        // For now, simulate success for testing
        return true;
        
        // Example Stripe integration:
        // try {
        //     \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        //     $transfer = \Stripe\Transfer::create([
        //         'amount' => (int) ($this->payout->net_amount * 100),
        //         'currency' => strtolower($this->payout->currency),
        //         'destination' => $this->payout->vendor->stripe_account_id,
        //         'source_transaction' => $this->payout->source_transaction_id,
        //     ]);
        //     return $transfer->status === 'succeeded';
        // } catch (\Exception $e) {
        //     Log::error('Stripe payout failed', ['error' => $e->getMessage()]);
        //     return false;
        // }
    }
}
