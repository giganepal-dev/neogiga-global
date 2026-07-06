<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerSegmentationService
{
    public function refresh(int $segmentId): int
    {
        $segment = DB::table('customer_segments')->find($segmentId);
        if (!$segment) return 0;
        $rules = json_decode($segment->rules ?: '[]', true) ?: [];
        $query = DB::table('customer_profiles');
        foreach ($rules as $field => $value) {
            if (in_array($field, ['country_id','region_id','city_id','customer_type','lifecycle_stage','marketing_opt_in','whatsapp_opt_in','status'], true)) {
                is_array($value) ? $query->whereIn($field, $value) : $query->where($field, $value);
            }
        }
        $ids = $query->pluck('id');
        DB::table('customer_segment_members')->where('customer_segment_id', $segmentId)->delete();
        foreach ($ids as $id) {
            DB::table('customer_segment_members')->insert(['customer_segment_id' => $segmentId, 'customer_profile_id' => $id, 'matched_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('customer_segments')->where('id', $segmentId)->update(['last_refreshed_at' => now(), 'updated_at' => now()]);
        return $ids->count();
    }

    public function create(array $data): int
    {
        return DB::table('customer_segments')->insertGetId(['name' => $data['name'], 'slug' => $data['slug'] ?? Str::slug($data['name']), 'description' => $data['description'] ?? null, 'rules' => json_encode($data['rules'] ?? []), 'type' => $data['type'] ?? 'dynamic', 'is_active' => $data['is_active'] ?? true, 'created_at' => now(), 'updated_at' => now()]);
    }
}
