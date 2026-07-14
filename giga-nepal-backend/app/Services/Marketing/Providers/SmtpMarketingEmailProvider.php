<?php

namespace App\Services\Marketing\Providers;

use App\Services\Marketing\Contracts\MarketingEmailProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class SmtpMarketingEmailProvider implements MarketingEmailProviderInterface
{
    public function createOrUpdateContact(array $contact): array
    {
        return $this->unsupported('contacts');
    }

    public function deleteOrSuppressContact(string $email, array $context = []): array
    {
        return $this->unsupported('suppressions');
    }

    public function addContactToList(string $email, string $list): array
    {
        return $this->unsupported('lists');
    }

    public function removeContactFromList(string $email, string $list): array
    {
        return $this->unsupported('lists');
    }

    public function sendCampaign(array $campaign): array
    {
        return $this->sendBatch($campaign['messages'] ?? []);
    }

    public function sendBatch(array $messages): array
    {
        $results = [];
        foreach ($messages as $message) {
            $to = (string) ($message['to'] ?? '');
            $fromEmail = (string) ($message['from']['email'] ?? '');
            if (! filter_var($to, FILTER_VALIDATE_EMAIL) || ! filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('SMTP campaign message requires valid to and from addresses.');
            }
            $html = (string) ($message['html'] ?? '');
            if ($html === '') {
                $html = nl2br(e((string) ($message['text'] ?? '')));
            }
            Mail::mailer('neogiga_marketing_smtp')->html($html, function ($mail) use ($message, $to, $fromEmail): void {
                $mail->to($to)
                    ->subject((string) ($message['subject'] ?? 'NeoGiga update'))
                    ->from($fromEmail, (string) ($message['from']['name'] ?? 'NeoGiga'));
                if (filter_var($message['reply_to'] ?? null, FILTER_VALIDATE_EMAIL)) {
                    $mail->replyTo((string) $message['reply_to']);
                }
            });
            $results[] = [
                'client_reference' => (string) ($message['client_reference'] ?? ''),
                'id' => 'smtp-'.Str::uuid(),
                'status' => 'sent',
            ];
        }

        return ['provider' => 'smtp', 'status' => 'accepted', 'sandbox' => false, 'accepted' => count($results), 'messages' => $results];
    }

    public function scheduleCampaign(array $campaign, string $scheduledAt): array
    {
        return ['provider' => 'smtp', 'status' => 'scheduled_locally', 'scheduled_at' => $scheduledAt, 'sandbox' => false];
    }

    public function cancelScheduledCampaign(string $providerCampaignId): array
    {
        return $this->unsupported('remote scheduling');
    }

    public function getCampaignStatus(string $providerCampaignId): array
    {
        return $this->unsupported('remote campaign status');
    }

    public function fetchEvents(array $cursor = []): array
    {
        return ['provider' => 'smtp', 'status' => 'not_supported', 'events' => [], 'sandbox' => false];
    }

    public function processWebhook(array $payload): array
    {
        return $this->unsupported('webhooks');
    }

    public function testConnection(): array
    {
        if ((bool) config('marketing.email.test_mode', true)) {
            return ['provider' => 'smtp', 'status' => 'configuration_valid', 'connected' => false, 'sandbox' => true];
        }
        $recipient = collect(config('marketing.email.test_recipients', []))->first();
        $profileId = config('marketing.email.sender_profile_id');
        $sender = $profileId ? DB::table('email_sender_profiles')->find($profileId) : DB::table('email_sender_profiles')->where('purpose', 'marketing')->where('is_verified', true)->where('is_enabled', true)->first();
        if (! $recipient || ! $sender || ! $sender->is_verified || ! $sender->is_enabled) {
            throw new RuntimeException('A test recipient and verified enabled marketing sender are required for an SMTP connection test.');
        }

        $result = $this->sendBatch([[
            'client_reference' => 'provider-test',
            'to' => $recipient,
            'subject' => 'NeoGiga SMTP provider test',
            'html' => '<p>This is an authorized NeoGiga SMTP provider test.</p>',
            'from' => ['name' => $sender->from_name, 'email' => $sender->from_email],
            'reply_to' => $sender->reply_to,
        ]]);

        return $result + ['connected' => true];
    }

    public function verifyWebhook(string $payload, ?string $signature): bool
    {
        return false;
    }

    private function unsupported(string $capability): array
    {
        return ['provider' => 'smtp', 'status' => 'not_supported', 'capability' => $capability, 'sandbox' => false];
    }
}
