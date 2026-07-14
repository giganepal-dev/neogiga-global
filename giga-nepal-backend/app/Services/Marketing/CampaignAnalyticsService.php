<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class CampaignAnalyticsService
{
    public function performance(int $campaignId): array
    {
        $campaign = DB::table('email_campaigns')->find($campaignId);
        if (! $campaign) {
            return [];
        }
        $snapshot = DB::table('campaign_audience_snapshots')->where('email_campaign_id', $campaignId)->orderByDesc('version')->first();
        $messages = DB::table('email_messages')->where('email_campaign_id', $campaignId);
        $messageIds = (clone $messages)->pluck('id');
        $events = DB::table('email_message_events')->whereIn('email_message_id', $messageIds);
        $countEvent = fn (array $types, bool $unique = false): int => (clone $events)->whereIn('normalized_event_type', $types)->when($unique, fn ($q) => $q->where('is_unique', true))->count();
        $planned = (int) ($snapshot->planned_count ?? $campaign->recipient_count ?? 0);
        $eligible = (int) ($snapshot->eligible_count ?? $campaign->eligible_count ?? 0);
        $sent = (clone $messages)->whereNotNull('sent_at')->count();
        $delivered = max((clone $messages)->whereNotNull('delivered_at')->count(), $countEvent(['delivered']));
        $opened = $countEvent(['opened']);
        $uniqueOpens = $countEvent(['opened'], true);
        $clicks = $countEvent(['clicked']);
        $uniqueClicks = $countEvent(['clicked'], true);
        $hard = $countEvent(['hard_bounce']);
        $soft = $countEvent(['soft_bounce']);
        $complaints = $countEvent(['complaint']);
        $unsubscribes = $countEvent(['unsubscribe']);
        $failed = (clone $messages)->whereIn('status', ['failed', 'hard_bounce', 'soft_bounce'])->count();

        return [
            'campaign_id' => $campaignId, 'channel' => 'email_campaign', 'name' => $campaign->name, 'status' => $campaign->status,
            'planned' => $planned, 'eligible' => $eligible, 'excluded' => (int) ($snapshot->excluded_count ?? $campaign->excluded_count ?? 0),
            'sent' => $sent, 'delivered' => $delivered, 'delivery_rate' => $this->rate($delivered, $sent),
            'opens' => $opened, 'unique_opens' => $uniqueOpens, 'open_rate' => $this->rate($uniqueOpens, $delivered),
            'clicks' => $clicks, 'unique_clicks' => $uniqueClicks, 'click_through_rate' => $this->rate($uniqueClicks, $delivered),
            'hard_bounces' => $hard, 'soft_bounces' => $soft, 'bounce_rate' => $this->rate($hard + $soft, $sent),
            'complaints' => $complaints, 'unsubscribes' => $unsubscribes, 'failed' => $failed,
            'snapshot_hash' => $snapshot->snapshot_hash ?? $campaign->audience_snapshot_hash,
            'tracking_note' => 'Open and device metrics are shown only when verified provider events exist; privacy controls and client behavior can limit accuracy.',
            'conversion_note' => 'Conversions are not reported unless reliable order attribution is present.',
        ];
    }

    public function campaigns(int $limit = 50): array
    {
        return DB::table('email_campaigns')->orderByDesc('id')->limit(max(1, min(100, $limit)))->pluck('id')->map(fn ($id) => $this->performance((int) $id))->all();
    }

    public function newsletterPerformance(int $campaignId): array
    {
        $campaign = DB::table('newsletter_campaigns')->find($campaignId);
        if (! $campaign) {
            return [];
        }
        $snapshot = DB::table('newsletter_audience_snapshots')->where('newsletter_campaign_id', $campaignId)->orderByDesc('version')->first();
        $messages = DB::table('email_messages')->where('newsletter_campaign_id', $campaignId);
        $messageIds = (clone $messages)->pluck('id');
        $events = DB::table('email_message_events')->whereIn('email_message_id', $messageIds);
        $countEvent = fn (array $types, bool $unique = false): int => (clone $events)->whereIn('normalized_event_type', $types)->when($unique, fn ($query) => $query->where('is_unique', true))->count();
        $sent = (clone $messages)->whereNotNull('sent_at')->count();
        $delivered = max((clone $messages)->whereNotNull('delivered_at')->count(), $countEvent(['delivered']));
        $opened = $countEvent(['opened']);
        $uniqueOpens = $countEvent(['opened'], true);
        $clicks = $countEvent(['clicked']);
        $uniqueClicks = $countEvent(['clicked'], true);
        $hard = $countEvent(['hard_bounce']);
        $soft = $countEvent(['soft_bounce']);
        $complaints = $countEvent(['complaint']);
        $unsubscribes = $countEvent(['unsubscribe']);

        return [
            'campaign_id' => $campaignId, 'channel' => 'newsletter', 'name' => $campaign->name, 'status' => $campaign->status,
            'planned' => (int) ($snapshot->planned_count ?? 0), 'eligible' => (int) ($snapshot->eligible_count ?? 0), 'excluded' => (int) ($snapshot->excluded_count ?? 0),
            'sent' => $sent, 'delivered' => $delivered, 'delivery_rate' => $this->rate($delivered, $sent),
            'opens' => $opened, 'unique_opens' => $uniqueOpens, 'open_rate' => $this->rate($uniqueOpens, $delivered),
            'clicks' => $clicks, 'unique_clicks' => $uniqueClicks, 'click_through_rate' => $this->rate($uniqueClicks, $delivered),
            'hard_bounces' => $hard, 'soft_bounces' => $soft, 'bounce_rate' => $this->rate($hard + $soft, $sent),
            'complaints' => $complaints, 'unsubscribes' => $unsubscribes,
            'failed' => (clone $messages)->whereIn('status', ['failed', 'hard_bounce', 'soft_bounce'])->count(),
            'snapshot_hash' => $snapshot->snapshot_hash ?? $campaign->audience_snapshot_hash,
            'tracking_note' => 'Open and device metrics are shown only when verified provider events exist; privacy controls and client behavior can limit accuracy.',
            'conversion_note' => 'Conversions are not reported unless reliable order attribution is present.',
        ];
    }

    public function newsletters(int $limit = 50): array
    {
        return DB::table('newsletter_campaigns')->orderByDesc('id')->limit(max(1, min(100, $limit)))->pluck('id')->map(fn ($id) => $this->newsletterPerformance((int) $id))->all();
    }

    public function countryDashboard(): array
    {
        return DB::table('countries as c')->leftJoin('customer_profiles as p', 'p.country_id', '=', 'c.id')
            ->selectRaw('c.id, c.name, c.iso_code_2, c.region, count(distinct p.id) as contact_count, count(distinct case when p.marketing_status = ? or p.marketing_opt_in = ? then p.id end) as potentially_marketable, count(distinct case when p.marketing_status in (?, ?) then p.id end) as transactional_or_unknown, max(p.last_order_at) as last_order_activity', ['opted_in', true, 'transactional_only', 'unknown'])
            ->groupBy('c.id', 'c.name', 'c.iso_code_2', 'c.region')->havingRaw('count(distinct p.id) > 0')->orderBy('c.name')->get()->map(function ($row): array {
                $suppressed = DB::table('customer_profiles as p')->join('suppression_lists as s', function ($join) {
                    $join->on('s.email', '=', 'p.email')->where('s.is_active', true);
                })->where('p.country_id', $row->id)->distinct('p.id')->count('p.id');
                $campaigns = DB::table('email_messages')->where('country_id', $row->id)->whereNotNull('email_campaign_id')->distinct('email_campaign_id')->count('email_campaign_id')
                    + DB::table('email_messages')->where('country_id', $row->id)->whereNotNull('newsletter_campaign_id')->distinct('newsletter_campaign_id')->count('newsletter_campaign_id');

                return (array) $row + ['suppressed_count' => $suppressed, 'campaigns_sent' => $campaigns, 'metric_note' => 'Marketable is a dashboard estimate; final campaign eligibility is recalculated at snapshot and send time.'];
            })->all();
    }

    private function rate(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 2) : 0.0;
    }
}
