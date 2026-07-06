<?php

namespace App\Http\Controllers\Api\Admin\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Marketing\WhatsAppCampaignExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WhatsappAdminController extends Controller
{
    use ApiResponses;

    public function templates(): JsonResponse
    {
        return $this->success(DB::table('whatsapp_templates')->get());
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'body' => 'nullable|string',
            'provider_template_name' => 'nullable|string|max:190',
        ]);

        $id = DB::table('whatsapp_templates')->insertGetId($data + [
            'slug' => Str::slug($data['name']),
            'approval_status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], 201);
    }

    public function campaigns(): JsonResponse
    {
        return $this->success(DB::table('whatsapp_campaigns')->orderByDesc('id')->get());
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'whatsapp_template_id' => 'nullable|integer',
            'targeting_rules' => 'array',
        ]);

        $id = DB::table('whatsapp_campaigns')->insertGetId([
            'name' => $data['name'],
            'whatsapp_template_id' => $data['whatsapp_template_id'] ?? null,
            'targeting_rules' => json_encode($data['targeting_rules'] ?? []),
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], 201);
    }

    public function preview(int $id): JsonResponse
    {
        return $this->success(DB::table('whatsapp_campaigns')->find($id));
    }

    public function schedule(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['scheduled_at' => 'nullable|date']);
        DB::table('whatsapp_campaigns')->where('id', $id)->update([
            'status' => 'scheduled',
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'updated_at' => now(),
        ]);

        return $this->success(['message' => 'Scheduled.']);
    }

    public function sendTest(Request $request, int $id, WhatsAppCampaignExecutionService $campaigns): JsonResponse
    {
        $data = $request->validate(['phone' => 'required|string|max:60']);

        return $this->success($campaigns->queueCampaign($id, true, $data['phone']));
    }

    public function sendNow(int $id, WhatsAppCampaignExecutionService $campaigns): JsonResponse
    {
        return $this->success($campaigns->queueCampaign($id));
    }

    public function events(): JsonResponse
    {
        return $this->success(DB::table('whatsapp_message_events')->orderByDesc('id')->limit(100)->get());
    }

    public function exportRecipients(Request $request, WhatsAppCampaignExecutionService $campaigns): JsonResponse
    {
        $data = $request->validate(['campaign_id' => 'nullable|integer']);

        return $this->success($campaigns->exportQueuedRecipients($data['campaign_id'] ?? null));
    }
}
