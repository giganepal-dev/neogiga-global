<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class NewsletterAudienceSnapshotService
{
    public function __construct(private EmailEligibilityService $eligibility) {}

    public function freeze(int $campaignId): array
    {
        $campaign = DB::table('newsletter_campaigns')->find($campaignId);
        if (! $campaign) {
            return ['error' => 'Newsletter campaign not found.', 'recipients' => []];
        }

        $existing = DB::table('newsletter_audience_snapshots')
            ->where('newsletter_campaign_id', $campaignId)
            ->where('status', 'frozen')
            ->orderByDesc('version')
            ->first();
        if ($existing) {
            return $this->existing($existing);
        }

        $rules = $this->decode($campaign->targeting_rules);
        $needsProfile = ! empty($campaign->marketplace_id) || ! empty($rules['segment_id']);
        $query = DB::table('newsletter_subscribers as s')
            ->select('s.id as newsletter_subscriber_id', 's.email', 's.name', 's.country_id', 's.region_id', 's.consent_status')
            ->where('s.status', 'subscribed')
            ->whereNotNull('s.email')
            ->when($needsProfile, fn ($builder) => $builder->join('customer_profiles as p', 'p.email', '=', 's.email'))
            ->when(! empty($campaign->marketplace_id), fn ($builder) => $builder->where('p.marketplace_id', (int) $campaign->marketplace_id))
            ->when(! empty($rules['segment_id']), fn ($builder) => $builder->join('customer_segment_members as sm', function ($join) use ($rules) {
                $join->on('sm.customer_profile_id', '=', 'p.id')->where('sm.customer_segment_id', (int) $rules['segment_id']);
            }))
            ->when(! empty($rules['country_id']), fn ($builder) => $builder->where('s.country_id', (int) $rules['country_id']))
            ->orderBy('s.id')
            ->limit(max(1, min((int) config('marketing.email.daily_limit', 5000), 100000)));

        $planned = $query->get();
        $version = ((int) DB::table('newsletter_audience_snapshots')->where('newsletter_campaign_id', $campaignId)->max('version')) + 1;
        $snapshotId = DB::table('newsletter_audience_snapshots')->insertGetId([
            'newsletter_campaign_id' => $campaignId,
            'version' => $version,
            'status' => 'preparing',
            'rules' => json_encode($rules),
            'planned_count' => $planned->count(),
            'snapshot_hash' => str_repeat('0', 64),
            'frozen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recipients = [];
        $exclusionTotals = [];
        foreach ($planned as $recipient) {
            $email = mb_strtolower(trim((string) $recipient->email));
            $decision = $this->eligibility->marketing($email, $recipient->consent_status === 'opted_in');
            $status = $decision['allowed'] ? 'eligible' : 'excluded';
            foreach ($decision['reasons'] as $reason) {
                $exclusionTotals[$reason] = ($exclusionTotals[$reason] ?? 0) + 1;
            }
            $snapshotHash = hash('sha256', $campaignId.'|'.$snapshotId.'|'.$email);
            $recipientId = DB::table('newsletter_campaign_recipients')->insertGetId([
                'newsletter_campaign_id' => $campaignId,
                'newsletter_subscriber_id' => $recipient->newsletter_subscriber_id,
                'newsletter_audience_snapshot_id' => $snapshotId,
                'email' => $email,
                'status' => $status,
                'eligibility_status' => $status,
                'eligibility_reasons' => json_encode($decision['reasons']),
                'snapshot_hash' => $snapshotHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($decision['allowed']) {
                $recipients[] = (array) $recipient + ['recipient_id' => $recipientId, 'email' => $email];
            }
        }

        $hashInput = DB::table('newsletter_campaign_recipients')
            ->where('newsletter_audience_snapshot_id', $snapshotId)
            ->orderBy('id')
            ->get(['email', 'eligibility_status'])
            ->map(fn ($recipient) => [hash('sha256', $recipient->email), $recipient->eligibility_status])
            ->all();
        $snapshotHash = hash('sha256', json_encode($hashInput));
        DB::table('newsletter_audience_snapshots')->where('id', $snapshotId)->update([
            'status' => 'frozen',
            'eligible_count' => count($recipients),
            'excluded_count' => $planned->count() - count($recipients),
            'exclusion_totals' => json_encode($exclusionTotals),
            'snapshot_hash' => $snapshotHash,
            'frozen_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('newsletter_campaigns')->where('id', $campaignId)->update(['audience_snapshot_hash' => $snapshotHash, 'updated_at' => now()]);

        return [
            'snapshot_id' => $snapshotId,
            'snapshot_hash' => $snapshotHash,
            'planned' => $planned->count(),
            'eligible' => count($recipients),
            'excluded' => $planned->count() - count($recipients),
            'exclusion_totals' => $exclusionTotals,
            'recipients' => $recipients,
            'reused' => false,
        ];
    }

    private function existing(object $snapshot): array
    {
        $recipients = DB::table('newsletter_campaign_recipients as r')
            ->leftJoin('newsletter_subscribers as s', 's.id', '=', 'r.newsletter_subscriber_id')
            ->where('r.newsletter_audience_snapshot_id', $snapshot->id)
            ->where('r.eligibility_status', 'eligible')
            ->select('r.id as recipient_id', 'r.email', 's.id as newsletter_subscriber_id', 's.name', 's.country_id', 's.region_id', 's.consent_status')
            ->orderBy('r.id')->get()->map(fn ($recipient) => (array) $recipient)->all();

        return [
            'snapshot_id' => $snapshot->id,
            'snapshot_hash' => $snapshot->snapshot_hash,
            'planned' => (int) $snapshot->planned_count,
            'eligible' => (int) $snapshot->eligible_count,
            'excluded' => (int) $snapshot->excluded_count,
            'exclusion_totals' => $this->decode($snapshot->exclusion_totals),
            'recipients' => $recipients,
            'reused' => true,
        ];
    }

    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = $value ? json_decode((string) $value, true) : [];

        return is_array($decoded) ? $decoded : [];
    }
}
