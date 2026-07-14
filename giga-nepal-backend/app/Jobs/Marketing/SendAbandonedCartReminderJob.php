<?php

namespace App\Jobs\Marketing;

use App\Services\Marketing\EmailQueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendAbandonedCartReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload = []) {}

    public function handle(EmailQueueService $emails): void
    {
        if (! Schema::hasTable('abandoned_carts') || ! Schema::hasTable('abandoned_cart_reminders')) {
            return;
        }

        $cartId = (int) ($this->payload['abandoned_cart_id'] ?? 0);
        $reminderId = (int) ($this->payload['reminder_id'] ?? 0);

        $query = DB::table('abandoned_carts as ac')
            ->leftJoin('abandoned_cart_reminders as r', 'r.abandoned_cart_id', '=', 'ac.id')
            ->where('ac.status', 'open')
            ->whereNotNull('ac.email')
            ->select('ac.*', 'r.id as reminder_id', 'r.reminder_number');

        if ($cartId > 0) {
            $query->where('ac.id', $cartId);
        } elseif ($reminderId > 0) {
            $query->where('r.id', $reminderId);
        } else {
            $query->where(function ($inner) {
                $inner->whereNull('r.id')
                    ->orWhere(function ($r) {
                        $r->where('r.status', 'queued')->where('r.scheduled_at', '<=', now());
                    });
            })->limit(max(1, min(250, (int) ($this->payload['limit'] ?? 50))));
        }

        $query->get()->each(function ($cart) use ($emails) {
            $reminderId = $cart->reminder_id ?: DB::table('abandoned_cart_reminders')->insertGetId([
                'abandoned_cart_id' => $cart->id,
                'channel' => 'email',
                'reminder_number' => 1,
                'status' => 'queued',
                'scheduled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $messageId = $emails->queue(
                $cart->email,
                'Complete your NeoGiga sourcing request',
                'Your NeoGiga cart is still available. Return to NeoGiga to review stock, RFQ options and checkout.',
                'abandoned_cart',
                ['abandoned_cart_id' => $cart->id, 'reminder_id' => $reminderId],
            );

            DB::table('abandoned_cart_reminders')->where('id', $reminderId)->update([
                'status' => 'email_queued',
                'sent_at' => now(),
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('email_message_events')) {
                DB::table('email_message_events')->insert([
                    'email_message_id' => $messageId,
                    'event_type' => 'abandoned_cart_reminder_queued',
                    'metadata' => json_encode(['abandoned_cart_id' => $cart->id, 'reminder_id' => $reminderId]),
                    'occurred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
