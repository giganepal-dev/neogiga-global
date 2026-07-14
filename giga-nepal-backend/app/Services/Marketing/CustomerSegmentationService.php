<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerSegmentationService
{
    public function refresh(int $segmentId): int
    {
        $segment = DB::table('customer_segments')->find($segmentId);
        if (! $segment) {
            return 0;
        }
        $rules = json_decode($segment->rules ?: '[]', true) ?: [];
        $query = DB::table('customer_profiles as customer_profiles');
        foreach ($rules as $field => $value) {
            if (in_array($field, ['country_id', 'region_id', 'city_id', 'customer_type', 'lifecycle_stage', 'marketing_opt_in', 'whatsapp_opt_in', 'marketing_status', 'status'], true)) {
                is_array($value) ? $query->whereIn("customer_profiles.{$field}", $value) : $query->where("customer_profiles.{$field}", $value);
            }
            if ($field === 'marketing_eligible' && $value) {
                $query->whereNotNull('customer_profiles.email')
                    ->where(function ($consent) {
                        $consent->where('customer_profiles.marketing_opt_in', true)
                            ->orWhereExists(function ($explicit) {
                                $explicit->selectRaw('1')->from('customer_consents as consent')
                                    ->whereColumn('consent.email', 'customer_profiles.email')
                                    ->where('consent.channel', 'email')
                                    ->where('consent.purpose', 'marketing')
                                    ->where('consent.granted', true)
                                    ->where('consent.status', 'opted_in');
                            });
                    })
                    ->whereNotExists(function ($suppression) {
                        $suppression->selectRaw('1')->from('suppression_lists as suppression')
                            ->whereColumn('suppression.email', 'customer_profiles.email')
                            ->where('suppression.channel', 'email')
                            ->where('suppression.is_active', true);
                    })
                    ->whereNotExists(function ($unsubscribe) {
                        $unsubscribe->selectRaw('1')->from('unsubscribes as unsubscribe')
                            ->whereColumn('unsubscribe.email', 'customer_profiles.email')
                            ->where('unsubscribe.channel', 'email');
                    });
            }
        }
        $ids = $query->pluck('customer_profiles.id');
        DB::table('customer_segment_members')->where('customer_segment_id', $segmentId)->delete();
        foreach ($ids as $id) {
            DB::table('customer_segment_members')->insert(['customer_segment_id' => $segmentId, 'customer_profile_id' => $id, 'matched_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('customer_segments')->where('id', $segmentId)->update(['last_refreshed_at' => now(), 'updated_at' => now()]);

        return $ids->count();
    }

    public function create(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $id = DB::table('customer_segments')->insertGetId(['name' => $data['name'], 'slug' => $data['slug'] ?? Str::slug($data['name']), 'description' => $data['description'] ?? null, 'rules' => json_encode($data['rules'] ?? []), 'type' => $data['type'] ?? 'dynamic', 'is_active' => $data['is_active'] ?? true, 'requires_consent_review' => ! (($data['rules']['marketing_eligible'] ?? false) === true), 'created_at' => now(), 'updated_at' => now()]);
            foreach (($data['rules'] ?? []) as $field => $value) {
                DB::table('customer_segment_rules')->insert(['customer_segment_id' => $id, 'field' => $field, 'operator' => is_array($value) ? 'in' : 'equals', 'value' => json_encode($value), 'created_at' => now(), 'updated_at' => now()]);
            }

            return $id;
        });
    }
}
