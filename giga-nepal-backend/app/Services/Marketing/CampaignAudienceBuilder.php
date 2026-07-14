<?php

namespace App\Services\Marketing;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CampaignAudienceBuilder
{
    public function __construct(private EmailEligibilityService $eligibility, private EmailPreferenceTokenService $tokens) {}

    public function count(array $filters = []): int
    {
        return $this->summary($filters)['eligible'];
    }

    public function summary(array $filters = []): array
    {
        $planned = 0;
        $eligible = 0;
        $reasons = [];
        $this->query($filters)->orderBy('p.id')->chunk(500, function ($rows) use (&$planned, &$eligible, &$reasons): void {
            foreach ($rows as $row) {
                $planned++;
                $decision = $this->eligibility->marketing($row->email, (bool) $row->marketing_opt_in);
                if ($decision['allowed']) {
                    $eligible++;
                }
                foreach ($decision['reasons'] as $reason) {
                    $reasons[$reason] = ($reasons[$reason] ?? 0) + 1;
                }
            }
        });
        ksort($reasons);

        return ['planned' => $planned, 'eligible' => $eligible, 'excluded' => $planned - $eligible, 'exclusion_totals' => $reasons, 'privacy_note' => 'Counts reflect current local consent and suppression state; provider-side suppression may further reduce delivery.'];
    }

    public function preview(array $filters = [], int $limit = 25): array
    {
        return $this->query($filters)->orderBy('p.id')->limit(max(1, min(100, $limit)))->get()->map(function ($row): array {
            $decision = $this->eligibility->marketing($row->email, (bool) $row->marketing_opt_in);

            return [
                'customer_profile_id' => $row->id,
                'name' => trim(($row->first_name ?? '').' '.($row->last_name ?? '')),
                'email_masked' => $this->tokens->mask($row->email),
                'country_id' => $row->country_id,
                'country_name' => $row->country_name,
                'eligible' => $decision['allowed'],
                'reasons' => $decision['reasons'],
            ];
        })->all();
    }

    private function query(array $filters): Builder
    {
        return DB::table('customer_profiles as p')->leftJoin('countries as c', 'c.id', '=', 'p.country_id')
            ->select('p.id', 'p.first_name', 'p.last_name', 'p.email', 'p.country_id', 'p.marketing_opt_in', 'c.name as country_name')
            ->whereNotNull('p.email')->where('p.status', 'active')
            ->when(! empty($filters['country_id']), fn ($q) => $q->whereIn('p.country_id', array_map('intval', (array) $filters['country_id'])))
            ->when(! empty($filters['region_id']), fn ($q) => $q->whereIn('p.region_id', array_map('intval', (array) $filters['region_id'])))
            ->when(! empty($filters['marketplace_id']), fn ($q) => $q->whereIn('p.marketplace_id', array_map('intval', (array) $filters['marketplace_id'])))
            ->when(! empty($filters['customer_type']), fn ($q) => $q->whereIn('p.customer_type', (array) $filters['customer_type']))
            ->when(! empty($filters['lifecycle_stage']), fn ($q) => $q->whereIn('p.lifecycle_stage', (array) $filters['lifecycle_stage']))
            ->when(isset($filters['minimum_orders']), fn ($q) => $q->where('p.total_orders', '>=', max(0, (int) $filters['minimum_orders'])))
            ->when(! empty($filters['active_since']), fn ($q) => $q->where(function ($active) use ($filters) {
                $active->where('p.last_seen_at', '>=', $filters['active_since'])->orWhere('p.last_order_at', '>=', $filters['active_since']);
            }))
            ->when(! empty($filters['email_domain']), fn ($q) => $q->whereRaw('LOWER(p.email) LIKE ?', ['%@'.mb_strtolower((string) $filters['email_domain'])]));
    }
}
