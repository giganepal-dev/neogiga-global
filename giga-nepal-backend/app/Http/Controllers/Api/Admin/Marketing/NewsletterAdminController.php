<?php

namespace App\Http\Controllers\Api\Admin\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Marketing\CampaignExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewsletterAdminController extends Controller
{
    use ApiResponses;

    public function subscribers(Request $request): JsonResponse
    {
        return $this->success(DB::table('newsletter_subscribers')->paginate((int) $request->query('per_page', 25)));
    }

    public function templates(): JsonResponse
    {
        return $this->success(DB::table('newsletter_templates')->orderByDesc('id')->get());
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'subject' => 'nullable|string|max:190',
            'html_body' => 'nullable|string',
            'text_body' => 'nullable|string',
        ]);

        $id = DB::table('newsletter_templates')->insertGetId($data + [
            'slug' => Str::slug($data['name']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], 201);
    }

    public function campaigns(): JsonResponse
    {
        return $this->success(DB::table('newsletter_campaigns')->orderByDesc('id')->get());
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'subject' => 'nullable|string|max:190',
            'newsletter_template_id' => 'nullable|integer',
            'targeting_rules' => 'array',
        ]);

        $id = DB::table('newsletter_campaigns')->insertGetId([
            'name' => $data['name'],
            'subject' => $data['subject'] ?? null,
            'newsletter_template_id' => $data['newsletter_template_id'] ?? null,
            'targeting_rules' => json_encode($data['targeting_rules'] ?? []),
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], 201);
    }

    public function preview(int $id): JsonResponse
    {
        return $this->success(DB::table('newsletter_campaigns')->find($id));
    }

    public function schedule(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['scheduled_at' => 'nullable|date']);
        DB::table('newsletter_campaigns')->where('id', $id)->update([
            'status' => 'scheduled',
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'updated_at' => now(),
        ]);

        return $this->success(['message' => 'Scheduled.']);
    }

    public function sendTest(Request $request, int $id, CampaignExecutionService $campaigns): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email']);

        return $this->success($campaigns->sendNewsletterCampaign($id, true, $data['email']));
    }

    public function sendNow(int $id, CampaignExecutionService $campaigns): JsonResponse
    {
        return $this->success($campaigns->sendNewsletterCampaign($id));
    }
}
