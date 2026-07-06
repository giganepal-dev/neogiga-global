<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampaignExecutionService
{
    public function __construct(private EmailTemplateService $templates)
    {
    }

    public function sendEmailCampaign(int $campaignId, bool $test = false, ?string $testEmail = null): array
    {
        $campaign = DB::table('email_campaigns')->find($campaignId);
        if (!$campaign) {
            return ['campaign_id' => $campaignId, 'eligible' => 0, 'queued' => 0, 'skipped' => 0, 'error' => 'Campaign not found.'];
        }

        $template = $campaign->email_template_id ? DB::table('email_templates')->find($campaign->email_template_id) : null;
        $recipients = $test ? $this->testRecipient($testEmail) : $this->emailAudience($this->rules($campaign->targeting_rules));

        $counts = ['campaign_id' => $campaignId, 'eligible' => count($recipients), 'queued' => 0, 'skipped' => 0, 'test_mode' => $test];

        foreach ($recipients as $recipient) {
            if (!$test && !$this->canEmail($recipient->email, (bool) ($recipient->marketing_opt_in ?? false))) {
                $counts['skipped']++;
                continue;
            }

            $recipientId = DB::table('email_campaign_recipients')->insertGetId([
                'email_campaign_id' => $campaignId,
                'customer_profile_id' => $recipient->customer_profile_id ?? null,
                'email' => $recipient->email,
                'status' => $test ? 'test_queued' : 'queued',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $messageId = DB::table('email_messages')->insertGetId([
                'email_template_id' => $template->id ?? null,
                'email_campaign_id' => $campaignId,
                'message_type' => $test ? 'marketing_test' : 'marketing',
                'provider' => 'log',
                'to_email' => $recipient->email,
                'subject' => $this->subject($template->subject ?? null, $campaign->name),
                'html_body' => $this->render($template->html_body ?? null, $recipient),
                'text_body' => $this->render($template->text_body ?? null, $recipient),
                'status' => $test ? 'test_queued' : 'queued',
                'metadata' => json_encode([
                    'safe_mode' => true,
                    'test' => $test,
                    'email_campaign_recipient_id' => $recipientId,
                    'source' => 'campaign_execution_service',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('email_message_events')->insert([
                'email_message_id' => $messageId,
                'event_type' => $test ? 'test_queued' : 'queued',
                'metadata' => json_encode(['safe_mode' => true, 'campaign_id' => $campaignId]),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counts['queued']++;
        }

        if (!$test && $counts['queued'] > 0) {
            DB::table('email_campaigns')->where('id', $campaignId)->update(['status' => 'queued', 'updated_at' => now()]);
        }

        return $counts;
    }

    public function sendNewsletterCampaign(int $campaignId, bool $test = false, ?string $testEmail = null): array
    {
        $campaign = DB::table('newsletter_campaigns')->find($campaignId);
        if (!$campaign) {
            return ['campaign_id' => $campaignId, 'eligible' => 0, 'queued' => 0, 'skipped' => 0, 'error' => 'Campaign not found.'];
        }

        $template = $campaign->newsletter_template_id ? DB::table('newsletter_templates')->find($campaign->newsletter_template_id) : null;
        $recipients = $test ? $this->testRecipient($testEmail) : $this->newsletterAudience($this->rules($campaign->targeting_rules));

        $counts = ['campaign_id' => $campaignId, 'eligible' => count($recipients), 'queued' => 0, 'skipped' => 0, 'test_mode' => $test];

        foreach ($recipients as $recipient) {
            if (!$test && !$this->isDeliverable($recipient->email)) {
                $counts['skipped']++;
                continue;
            }

            $recipientId = DB::table('newsletter_campaign_recipients')->insertGetId([
                'newsletter_campaign_id' => $campaignId,
                'newsletter_subscriber_id' => $recipient->newsletter_subscriber_id ?? null,
                'email' => $recipient->email,
                'status' => $test ? 'test_queued' : 'queued',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('newsletter_campaign_events')->insert([
                'newsletter_campaign_id' => $campaignId,
                'newsletter_campaign_recipient_id' => $recipientId,
                'event_type' => $test ? 'test_queued' : 'queued',
                'metadata' => json_encode(['safe_mode' => true, 'test' => $test]),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('email_messages')->insert([
                'email_template_id' => null,
                'email_campaign_id' => null,
                'message_type' => $test ? 'newsletter_test' : 'newsletter',
                'provider' => 'log',
                'to_email' => $recipient->email,
                'subject' => $this->subject($campaign->subject ?? $template->subject ?? null, $campaign->name),
                'html_body' => $this->render($template->html_body ?? null, $recipient),
                'text_body' => $this->render($template->text_body ?? null, $recipient),
                'status' => $test ? 'test_queued' : 'queued',
                'metadata' => json_encode([
                    'safe_mode' => true,
                    'test' => $test,
                    'newsletter_campaign_id' => $campaignId,
                    'newsletter_campaign_recipient_id' => $recipientId,
                    'source' => 'campaign_execution_service',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counts['queued']++;
        }

        if (!$test && $counts['queued'] > 0) {
            DB::table('newsletter_campaigns')->where('id', $campaignId)->update(['status' => 'queued', 'updated_at' => now()]);
        }

        return $counts;
    }

    private function emailAudience(array $rules): array
    {
        return DB::table('customer_profiles as c')
            ->selectRaw('c.id as customer_profile_id, c.email, c.first_name, c.last_name, c.country_id, c.region_id, c.marketing_opt_in')
            ->whereNotNull('c.email')
            ->where('c.status', 'active')
            ->when(isset($rules['segment_id']), fn ($q) => $q->join('customer_segment_members as sm', function ($join) use ($rules) {
                $join->on('sm.customer_profile_id', '=', 'c.id')->where('sm.customer_segment_id', (int) $rules['segment_id']);
            }))
            ->when(isset($rules['country_id']), fn ($q) => $q->where('c.country_id', (int) $rules['country_id']))
            ->orderBy('c.id')
            ->limit($this->dailyLimit())
            ->get()
            ->all();
    }

    private function newsletterAudience(array $rules): array
    {
        return DB::table('newsletter_subscribers as n')
            ->selectRaw('n.id as newsletter_subscriber_id, n.email, n.name, n.country_id, n.region_id')
            ->where('n.status', 'subscribed')
            ->whereNotNull('n.email')
            ->when(isset($rules['country_id']), fn ($q) => $q->where('n.country_id', (int) $rules['country_id']))
            ->when(isset($rules['segment_id']), fn ($q) => $q->whereExists(function ($sub) use ($rules) {
                $sub->selectRaw('1')
                    ->from('customer_profiles as c')
                    ->join('customer_segment_members as sm', 'sm.customer_profile_id', '=', 'c.id')
                    ->whereColumn('c.email', 'n.email')
                    ->where('sm.customer_segment_id', (int) $rules['segment_id']);
            }))
            ->orderBy('n.id')
            ->limit($this->dailyLimit())
            ->get()
            ->all();
    }

    private function canEmail(string $email, bool $marketingOptIn): bool
    {
        if (!$this->isDeliverable($email)) {
            return false;
        }

        if ($marketingOptIn) {
            return true;
        }

        return DB::table('customer_consents')
            ->where('email', $email)
            ->where('channel', 'email')
            ->where('purpose', 'marketing')
            ->where('granted', true)
            ->exists();
    }

    private function isDeliverable(string $email): bool
    {
        if (DB::table('suppression_lists')->where('channel', 'email')->where('email', $email)->exists()) {
            return false;
        }

        return !DB::table('unsubscribes')->where('channel', 'email')->where('email', $email)->exists();
    }

    private function testRecipient(?string $email): array
    {
        return [(object) [
            'email' => $email,
            'customer_profile_id' => null,
            'newsletter_subscriber_id' => null,
            'first_name' => 'Test',
            'last_name' => 'Recipient',
            'name' => 'Test Recipient',
            'marketing_opt_in' => true,
        ]];
    }

    private function render(?string $body, object $recipient): string
    {
        return $this->templates->render($body, [
            'customer_name' => trim(($recipient->first_name ?? '').' '.($recipient->last_name ?? '')) ?: ($recipient->name ?? 'Customer'),
            'email' => $recipient->email,
            'country' => $recipient->country_id ?? '',
            'region' => $recipient->region_id ?? '',
            'marketplace_name' => 'NeoGiga',
            'unsubscribe_url' => url('/api/unsubscribe?email='.urlencode((string) $recipient->email)),
        ]);
    }

    private function subject(?string $subject, string $fallback): string
    {
        return Str::limit($subject ?: $fallback, 190, '');
    }

    private function rules(mixed $rules): array
    {
        if (is_array($rules)) {
            return $rules;
        }

        if (!$rules) {
            return [];
        }

        $decoded = json_decode((string) $rules, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function dailyLimit(): int
    {
        $setting = DB::table('marketing_settings')->where('key', 'campaign_daily_limit')->value('value');
        $decoded = $setting ? json_decode((string) $setting, true) : null;
        $limit = is_numeric($decoded) ? (int) $decoded : 5000;

        return max(1, min($limit, 100000));
    }
}
