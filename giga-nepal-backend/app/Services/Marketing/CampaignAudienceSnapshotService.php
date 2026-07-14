<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class CampaignAudienceSnapshotService
{
    public function __construct(private EmailEligibilityService $eligibility) {}

    public function freeze(int $campaignId): array
    {
        $campaign = DB::table('email_campaigns')->find($campaignId);
        if (! $campaign) {
            return ['error' => 'Campaign not found.', 'recipients' => []];
        }
        $existing = DB::table('campaign_audience_snapshots')->where('email_campaign_id', $campaignId)->where('status', 'frozen')->orderByDesc('version')->first();
        if ($existing) {
            return $this->existing($existing);
        }
        $rules = $this->decode($campaign->targeting_rules);
        $countryIds = $this->decode($campaign->target_country_ids ?? null);
        $query = DB::table('customer_profiles as p')
            ->select('p.id as customer_profile_id', 'p.email', 'p.first_name', 'p.last_name', 'p.country_id', 'p.region_id', 'p.marketing_opt_in', 'p.marketing_status')
            ->whereNotNull('p.email')->where('p.status', 'active')
            ->when(isset($rules['segment_id']), fn ($q) => $q->join('customer_segment_members as sm', function ($join) use ($rules) {
                $join->on('sm.customer_profile_id', '=', 'p.id')->where('sm.customer_segment_id', (int) $rules['segment_id']);
            }))
            ->when(isset($rules['country_id']), fn ($q) => $q->where('p.country_id', (int) $rules['country_id']))
            ->when($countryIds !== [], fn ($q) => $q->whereIn('p.country_id', array_map('intval', $countryIds)))
            ->orderBy('p.id')
            ->limit(max(1, min((int) config('marketing.email.daily_limit', 5000), 100000)));
        $planned = $query->get();
        $version = ((int) DB::table('campaign_audience_snapshots')->where('email_campaign_id', $campaignId)->max('version')) + 1;
        $snapshotId = DB::table('campaign_audience_snapshots')->insertGetId([
            'email_campaign_id' => $campaignId,
            'version' => $version,
            'status' => 'preparing',
            'rules' => json_encode($rules),
            'exclusions' => $campaign->exclusions ?? null,
            'planned_count' => $planned->count(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recipients = [];
        $exclusionTotals = [];
        foreach ($planned as $recipient) {
            $decision = $this->eligibility->marketing($recipient->email, (bool) $recipient->marketing_opt_in);
            $status = $decision['allowed'] ? 'eligible' : 'excluded';
            foreach ($decision['reasons'] as $reason) {
                $exclusionTotals[$reason] = ($exclusionTotals[$reason] ?? 0) + 1;
            }
            $key = hash('sha256', $campaignId.'|'.$snapshotId.'|'.mb_strtolower($recipient->email));
            $recipientId = DB::table('email_campaign_recipients')->insertGetId([
                'email_campaign_id' => $campaignId,
                'customer_profile_id' => $recipient->customer_profile_id,
                'audience_snapshot_id' => $snapshotId,
                'email' => mb_strtolower($recipient->email),
                'status' => $status,
                'eligibility_status' => $status,
                'eligibility_reasons' => json_encode($decision['reasons']),
                'idempotency_key' => $key,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $recipients[] = (array) $recipient + ['recipient_id' => $recipientId, 'eligibility' => $decision];
        }
        $eligible = array_values(array_filter($recipients, fn ($recipient) => $recipient['eligibility']['allowed']));
        $snapshotHash = hash('sha256', json_encode(array_map(fn ($recipient) => [$recipient['customer_profile_id'], hash('sha256', mb_strtolower($recipient['email'])), $recipient['eligibility']['allowed']], $recipients)));
        DB::table('campaign_audience_snapshots')->where('id', $snapshotId)->update([
            'status' => 'frozen',
            'eligible_count' => count($eligible),
            'excluded_count' => count($recipients) - count($eligible),
            'exclusion_totals' => json_encode($exclusionTotals),
            'snapshot_hash' => $snapshotHash,
            'frozen_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('email_campaigns')->where('id', $campaignId)->update([
            'recipient_count' => count($recipients),
            'eligible_count' => count($eligible),
            'excluded_count' => count($recipients) - count($eligible),
            'audience_snapshot_hash' => $snapshotHash,
            'updated_at' => now(),
        ]);

        return ['snapshot_id' => $snapshotId, 'snapshot_hash' => $snapshotHash, 'planned' => count($recipients), 'eligible' => count($eligible), 'excluded' => count($recipients) - count($eligible), 'exclusion_totals' => $exclusionTotals, 'recipients' => $eligible];
    }

    private function existing(object $snapshot): array
    {
        $recipients = DB::table('email_campaign_recipients as r')
            ->leftJoin('customer_profiles as p', 'p.id', '=', 'r.customer_profile_id')
            ->where('r.audience_snapshot_id', $snapshot->id)->where('r.eligibility_status', 'eligible')
            ->select('r.id as recipient_id', 'r.email', 'p.id as customer_profile_id', 'p.first_name', 'p.last_name', 'p.country_id', 'p.region_id', 'p.marketing_opt_in', 'p.marketing_status')
            ->orderBy('r.id')->get()->map(fn ($recipient) => (array) $recipient + ['eligibility' => ['allowed' => true, 'reasons' => []]])->all();

        return [
            'snapshot_id' => $snapshot->id, 'snapshot_hash' => $snapshot->snapshot_hash,
            'planned' => (int) $snapshot->planned_count, 'eligible' => (int) $snapshot->eligible_count,
            'excluded' => (int) $snapshot->excluded_count,
            'exclusion_totals' => $this->decode($snapshot->exclusion_totals), 'recipients' => $recipients,
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
