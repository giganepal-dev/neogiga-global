<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class WhatsAppCampaignExecutionService
{
    public function queueCampaign(int $campaignId, bool $test = false, ?string $testPhone = null): array
    {
        $campaign = DB::table('whatsapp_campaigns')->find($campaignId);
        if (!$campaign) {
            return ['campaign_id' => $campaignId, 'eligible' => 0, 'queued' => 0, 'skipped' => 0, 'error' => 'Campaign not found.'];
        }

        $template = $campaign->whatsapp_template_id ? DB::table('whatsapp_templates')->find($campaign->whatsapp_template_id) : null;
        $recipients = $test ? $this->testRecipient($testPhone) : $this->audience($this->rules($campaign->targeting_rules));

        $counts = ['campaign_id' => $campaignId, 'eligible' => count($recipients), 'queued' => 0, 'skipped' => 0, 'test_mode' => $test];

        foreach ($recipients as $recipient) {
            if (!$test && !$this->canQueue($recipient->phone)) {
                $counts['skipped']++;
                continue;
            }

            $recipientId = DB::table('whatsapp_campaign_recipients')->insertGetId([
                'whatsapp_campaign_id' => $campaignId,
                'customer_profile_id' => $recipient->customer_profile_id ?? null,
                'phone' => $recipient->phone,
                'status' => $test ? 'test_queued' : 'queued',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $messageId = DB::table('whatsapp_messages')->insertGetId([
                'whatsapp_template_id' => $template->id ?? null,
                'whatsapp_campaign_id' => $campaignId,
                'provider' => 'manual_export',
                'to_phone' => $recipient->phone,
                'body' => $this->render($template->body ?? null, $recipient),
                'status' => $test ? 'test_queued' : 'queued',
                'metadata' => json_encode([
                    'safe_mode' => true,
                    'manual_export_only' => true,
                    'test' => $test,
                    'whatsapp_campaign_recipient_id' => $recipientId,
                    'source' => 'whatsapp_campaign_execution_service',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('whatsapp_message_events')->insert([
                'whatsapp_message_id' => $messageId,
                'event_type' => $test ? 'test_queued' : 'queued',
                'metadata' => json_encode(['safe_mode' => true, 'manual_export_only' => true, 'campaign_id' => $campaignId]),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counts['queued']++;
        }

        if (!$test && $counts['queued'] > 0) {
            DB::table('whatsapp_campaigns')->where('id', $campaignId)->update(['status' => 'queued', 'updated_at' => now()]);
        }

        return $counts;
    }

    public function exportQueuedRecipients(?int $campaignId = null): array
    {
        $rows = DB::table('whatsapp_campaign_recipients as r')
            ->leftJoin('whatsapp_campaigns as c', 'c.id', '=', 'r.whatsapp_campaign_id')
            ->select('r.whatsapp_campaign_id', 'c.name as campaign_name', 'r.phone', 'r.status', 'r.created_at')
            ->whereIn('r.status', ['queued', 'test_queued'])
            ->when($campaignId, fn ($query) => $query->where('r.whatsapp_campaign_id', $campaignId))
            ->orderByDesc('r.id')
            ->limit(1000)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return ['count' => count($rows), 'provider' => 'manual_export', 'rows' => $rows];
    }

    private function audience(array $rules): array
    {
        return DB::table('whatsapp_opt_ins as o')
            ->leftJoin('customer_profiles as c', function ($join) {
                $join->on('c.whatsapp_number', '=', 'o.phone')->orOn('c.phone', '=', 'o.phone');
            })
            ->selectRaw('o.phone, c.id as customer_profile_id, c.first_name, c.last_name, c.country_id, c.region_id, c.whatsapp_opt_in')
            ->where('o.opted_in', true)
            ->whereNotNull('o.phone')
            ->when(isset($rules['segment_id']), fn ($query) => $query->join('customer_segment_members as sm', function ($join) use ($rules) {
                $join->on('sm.customer_profile_id', '=', 'c.id')->where('sm.customer_segment_id', (int) $rules['segment_id']);
            }))
            ->when(isset($rules['country_id']), fn ($query) => $query->where('c.country_id', (int) $rules['country_id']))
            ->orderBy('o.id')
            ->limit($this->dailyLimit())
            ->get()
            ->all();
    }

    private function canQueue(string $phone): bool
    {
        if (DB::table('suppression_lists')->where('channel', 'whatsapp')->where('phone', $phone)->exists()) {
            return false;
        }

        return DB::table('whatsapp_opt_ins')->where('phone', $phone)->where('opted_in', true)->exists();
    }

    private function testRecipient(?string $phone): array
    {
        return [(object) [
            'phone' => $phone,
            'customer_profile_id' => null,
            'first_name' => 'Test',
            'last_name' => 'Recipient',
        ]];
    }

    private function render(?string $body, object $recipient): string
    {
        $body = $body ?: 'NeoGiga campaign message queued for manual WhatsApp export.';
        $name = trim(($recipient->first_name ?? '').' '.($recipient->last_name ?? '')) ?: 'Customer';

        return str_replace(
            ['{{customer_name}}', '{{phone}}', '{{unsubscribe_url}}'],
            [$name, $recipient->phone, url('/api/whatsapp/opt-out')],
            $body
        );
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
