<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class CampaignSuppressionService
{
    public function __construct(private EmailEligibilityService $eligibility) {}

    public function count(array $filters = []): int
    {
        return count($this->eligible($filters));
    }

    public function preview(array $filters = [], int $limit = 25): array
    {
        return array_slice($this->eligible($filters), 0, max(1, min($limit, 100)));
    }

    private function eligible(array $filters): array
    {
        return DB::table('customer_profiles')->whereNotNull('email')->where('status', 'active')
            ->when(isset($filters['country_id']), fn ($q) => $q->where('country_id', $filters['country_id']))
            ->get()->map(function ($profile) {
                $decision = $this->eligibility->marketing($profile->email, (bool) $profile->marketing_opt_in);

                return $decision['allowed'] ? (array) $profile + ['eligibility' => $decision] : null;
            })->filter()->values()->all();
    }
}
