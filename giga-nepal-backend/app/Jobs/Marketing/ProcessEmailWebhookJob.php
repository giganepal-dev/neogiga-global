<?php

namespace App\Jobs\Marketing;

use App\Services\Marketing\EmailWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEmailWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [15, 60, 300, 900];

    public function __construct(public int $eventId)
    {
        $this->onQueue((string) config('marketing.webhooks.queue', 'webhooks'));
    }

    public function handle(EmailWebhookService $webhooks): void
    {
        $webhooks->process($this->eventId);
    }
}
