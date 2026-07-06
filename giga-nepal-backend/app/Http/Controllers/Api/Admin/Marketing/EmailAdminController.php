<?php

namespace App\Http\Controllers\Api\Admin\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Marketing\CampaignExecutionService;
use App\Services\Marketing\EmailCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailAdminController extends Controller
{
    use ApiResponses;

    public function templates(): JsonResponse
    {
        return $this->success(DB::table('email_templates')->orderBy('type')->get());
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'type' => 'required|string|max:80',
            'subject' => 'required|string|max:190',
            'html_body' => 'nullable|string',
            'text_body' => 'nullable|string',
            'is_transactional' => 'boolean',
        ]);

        $id = DB::table('email_templates')->insertGetId($data + [
            'slug' => Str::slug($data['name']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], 201);
    }

    public function updateTemplate(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:190',
            'subject' => 'sometimes|string|max:190',
            'html_body' => 'nullable|string',
            'text_body' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::table('email_templates')->where('id', $id)->update($data + ['updated_at' => now()]);

        return $this->success(['message' => 'Updated.']);
    }

    public function campaigns(): JsonResponse
    {
        return $this->success(DB::table('email_campaigns')->orderByDesc('id')->get());
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'type' => 'nullable|string|max:40',
            'email_template_id' => 'nullable|integer',
            'targeting_rules' => 'array',
        ]);

        $id = DB::table('email_campaigns')->insertGetId([
            'name' => $data['name'],
            'type' => $data['type'] ?? 'marketing',
            'email_template_id' => $data['email_template_id'] ?? null,
            'targeting_rules' => json_encode($data['targeting_rules'] ?? []),
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], 201);
    }

    public function preview(int $id): JsonResponse
    {
        return $this->success(DB::table('email_campaigns')->find($id));
    }

    public function schedule(Request $request, int $id, EmailCampaignService $campaigns): JsonResponse
    {
        $data = $request->validate(['scheduled_at' => 'nullable|date']);
        $campaigns->schedule($id, $data['scheduled_at'] ?? null);

        return $this->success(['message' => 'Scheduled.']);
    }

    public function sendTest(Request $request, int $id, CampaignExecutionService $campaigns): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email']);

        return $this->success($campaigns->sendEmailCampaign($id, true, $data['email']));
    }

    public function sendNow(int $id, CampaignExecutionService $campaigns): JsonResponse
    {
        return $this->success($campaigns->sendEmailCampaign($id));
    }

    public function events(): JsonResponse
    {
        return $this->success(DB::table('email_message_events')->orderByDesc('id')->limit(100)->get());
    }

    public function automationRules(): JsonResponse
    {
        return $this->success(DB::table('email_automation_rules')->orderBy('trigger')->get());
    }

    public function storeAutomationRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'trigger' => 'required|string|max:120',
            'email_template_id' => 'nullable|integer',
            'conditions' => 'array',
            'delay_minutes' => 'integer',
            'is_active' => 'boolean',
        ]);

        $id = DB::table('email_automation_rules')->insertGetId([
            'name' => $data['name'],
            'trigger' => $data['trigger'],
            'email_template_id' => $data['email_template_id'] ?? null,
            'conditions' => json_encode($data['conditions'] ?? []),
            'delay_minutes' => $data['delay_minutes'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], 201);
    }

    public function updateAutomationRule(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:190',
            'conditions' => 'array',
            'delay_minutes' => 'integer',
            'is_active' => 'boolean',
        ]);

        if (isset($data['conditions'])) {
            $data['conditions'] = json_encode($data['conditions']);
        }

        DB::table('email_automation_rules')->where('id', $id)->update($data + ['updated_at' => now()]);

        return $this->success(['message' => 'Updated.']);
    }
}
