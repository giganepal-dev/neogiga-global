<?php

namespace App\Http\Controllers\Api\Admin\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsAdminController extends Controller
{
    use ApiResponses;

    private const MARKETING_KEYS = ['newsletter_double_opt_in', 'campaign_daily_limit', 'campaign_send_window_start', 'campaign_send_window_end', 'soft_bounce_threshold', 'tracking_opens_enabled', 'tracking_clicks_enabled'];

    private const ANALYTICS_KEYS = ['ga_measurement_id', 'retention_days', 'privacy_mode', 'order_attribution_window_days'];

    public function marketing(): JsonResponse
    {
        return $this->success($this->safeSettings('marketing_settings'));
    }

    public function analytics(): JsonResponse
    {
        return $this->success($this->safeSettings('analytics_settings'));
    }

    public function updateMarketing(Request $request): JsonResponse
    {
        $data = $request->validate(['settings' => 'required|array']);

        return $this->store('marketing_settings', 'marketing', $data['settings'], self::MARKETING_KEYS);
    }

    public function updateAnalytics(Request $request): JsonResponse
    {
        $data = $request->validate(['settings' => 'required|array']);

        return $this->store('analytics_settings', 'analytics', $data['settings'], self::ANALYTICS_KEYS);
    }

    private function store(string $table, string $group, array $settings, array $allowed): JsonResponse
    {
        $unknown = array_values(array_diff(array_keys($settings), $allowed));
        if ($unknown !== []) {
            return $this->error('Unsupported or secret-like settings are not accepted: '.implode(', ', $unknown), 422);
        }
        foreach ($settings as $key => $value) {
            DB::table($table)->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'group' => $group, 'updated_at' => now(), 'created_at' => now()]);
        }

        return $this->success(['message' => ucfirst($group).' operational settings updated. Provider credentials remain isolated in encrypted provider configuration.']);
    }

    private function safeSettings(string $table): array
    {
        return DB::table($table)->get()->reject(fn ($row) => preg_match('/secret|password|token|api[_-]?key|credential/i', (string) $row->key))->values()->all();
    }
}
