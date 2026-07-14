<?php

namespace App\Http\Controllers\Api\Admin\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Jobs\Marketing\SendEmailCampaignJob;
use App\Services\Marketing\CampaignExecutionService;
use App\Services\Marketing\EmailCampaignService;
use App\Services\Marketing\EmailProviderConfigurationService;
use App\Services\Marketing\EmailProviderManager;
use App\Services\Marketing\EmailTemplateValidator;
use App\Services\Marketing\MarketingEmailProviderManager;
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

        $validation = app(EmailTemplateValidator::class)->validate($data, ! ($data['is_transactional'] ?? false));
        if (! $validation['valid']) {
            return $this->error('Template validation failed: '.implode(', ', $validation['errors']), 422);
        }
        $slug = $this->uniqueSlug($data['name']);
        $id = DB::transaction(function () use ($data, $slug, $validation): int {
            $id = DB::table('email_templates')->insertGetId($data + ['slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('email_template_versions')->insert([
                'email_template_id' => $id, 'version' => 1, 'subject' => $data['subject'], 'html_body' => $data['html_body'] ?? null,
                'text_body' => $data['text_body'] ?? null, 'variables' => json_encode($validation['variables']),
                'validation_results' => json_encode($validation), 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $id;
        });

        return $this->success(['id' => $id, 'validation' => $validation], 201);
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

        $template = DB::table('email_templates')->find($id);
        if (! $template) {
            return $this->error('Template not found.', 404);
        }
        $merged = array_merge((array) $template, $data);
        $validation = app(EmailTemplateValidator::class)->validate($merged, ! (bool) $template->is_transactional);
        if (! $validation['valid']) {
            return $this->error('Template validation failed: '.implode(', ', $validation['errors']), 422);
        }
        DB::transaction(function () use ($id, $data, $merged, $validation): void {
            DB::table('email_templates')->where('id', $id)->update($data + ['updated_at' => now()]);
            $version = ((int) DB::table('email_template_versions')->where('email_template_id', $id)->max('version')) + 1;
            DB::table('email_template_versions')->insert([
                'email_template_id' => $id, 'version' => $version, 'subject' => $merged['subject'],
                'html_body' => $merged['html_body'] ?? null, 'text_body' => $merged['text_body'] ?? null,
                'variables' => json_encode($validation['variables']), 'validation_results' => json_encode($validation),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return $this->success(['message' => 'Updated.', 'validation' => $validation]);
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
            'preview_text' => 'nullable|string|max:255',
            'reply_to' => 'nullable|email|max:190',
            'marketplace_id' => 'nullable|integer',
            'target_country_ids' => 'nullable|array|max:250',
            'target_country_ids.*' => 'integer',
            'exclusions' => 'nullable|array',
            'language' => 'nullable|string|max:12',
            'timezone' => 'nullable|timezone',
        ]);

        $id = DB::table('email_campaigns')->insertGetId([
            'name' => $data['name'],
            'type' => $data['type'] ?? 'marketing',
            'email_template_id' => $data['email_template_id'] ?? null,
            'targeting_rules' => json_encode($data['targeting_rules'] ?? []),
            'preview_text' => $data['preview_text'] ?? null,
            'reply_to' => $data['reply_to'] ?? null,
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'target_country_ids' => json_encode($data['target_country_ids'] ?? []),
            'exclusions' => json_encode($data['exclusions'] ?? []),
            'language' => $data['language'] ?? 'en',
            'timezone' => $data['timezone'] ?? 'UTC',
            'internal_reference' => 'CAM-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
            'requires_approval' => true,
            'production_send_enabled' => false,
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
        $campaign = DB::table('email_campaigns')->find($id);
        if (! $campaign) {
            return $this->error('Campaign not found.', 404);
        }
        if (($campaign->requires_approval ?? true) && ! $campaign->approved_at) {
            return $this->error('Campaign approval is required before scheduling.', 422);
        }
        $campaigns->schedule($id, $data['scheduled_at'] ?? null);

        return $this->success(['message' => 'Scheduled.']);
    }

    public function sendTest(Request $request, int $id, CampaignExecutionService $campaigns): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email']);
        SendEmailCampaignJob::dispatch(['campaign_id' => $id, 'test' => true, 'test_email' => mb_strtolower($data['email'])]);

        return $this->success(['status' => 'queued', 'queue' => config('marketing.email.queue'), 'test_recipient' => mb_strtolower($data['email'])], 202);
    }

    public function sendNow(int $id, CampaignExecutionService $campaigns): JsonResponse
    {
        SendEmailCampaignJob::dispatch(['campaign_id' => $id]);

        return $this->success(['status' => 'queued', 'queue' => config('marketing.email.queue')], 202);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $campaign = DB::table('email_campaigns')->find($id);
        if (! $campaign) {
            return $this->error('Campaign not found.', 404);
        }
        $template = $campaign->email_template_id ? DB::table('email_templates')->find($campaign->email_template_id) : null;
        if (! $template) {
            return $this->error('An active template is required.', 422);
        }
        $validation = app(EmailTemplateValidator::class)->validate($template, true);
        if (! $validation['valid']) {
            return $this->error('Template validation failed: '.implode(', ', $validation['errors']), 422);
        }
        DB::table('email_campaigns')->where('id', $id)->update([
            'status' => 'approved', 'approved_by' => $request->user()?->id, 'approved_at' => now(),
            'production_send_enabled' => $request->boolean('production_send_enabled', false), 'updated_at' => now(),
        ]);

        return $this->success(['message' => 'Campaign approved.', 'production_send_enabled' => $request->boolean('production_send_enabled', false), 'validation' => $validation]);
    }

    public function pause(int $id): JsonResponse
    {
        $updated = DB::table('email_campaigns')->where('id', $id)->whereNull('cancelled_at')->update(['status' => 'paused', 'paused_at' => now(), 'updated_at' => now()]);

        return $updated ? $this->success(['message' => 'Campaign paused.']) : $this->error('Campaign not found or already cancelled.', 404);
    }

    public function resume(int $id): JsonResponse
    {
        $campaign = DB::table('email_campaigns')->find($id);
        if (! $campaign || $campaign->cancelled_at) {
            return $this->error('Campaign not found or cancelled.', 404);
        }
        if (! $campaign->approved_at) {
            return $this->error('Campaign approval is required.', 422);
        }
        DB::table('email_campaigns')->where('id', $id)->update(['status' => $campaign->scheduled_at ? 'scheduled' : 'approved', 'paused_at' => null, 'updated_at' => now()]);

        return $this->success(['message' => 'Campaign resumed.']);
    }

    public function cancel(int $id): JsonResponse
    {
        $updated = DB::table('email_campaigns')->where('id', $id)->whereNull('cancelled_at')->update(['status' => 'cancelled', 'cancelled_at' => now(), 'production_send_enabled' => false, 'updated_at' => now()]);
        if ($updated) {
            DB::table('email_messages')->where('email_campaign_id', $id)->whereIn('status', ['queued', 'scheduled'])->update(['status' => 'cancelled', 'updated_at' => now()]);
        }

        return $updated ? $this->success(['message' => 'Campaign cancelled.']) : $this->error('Campaign not found or already cancelled.', 404);
    }

    public function providerTest(MarketingEmailProviderManager $providers, EmailProviderConfigurationService $configuration): JsonResponse
    {
        $result = $providers->testConnection();
        $configuration->markTested('marketing', (string) ($result['status'] ?? 'unknown'));

        return $this->success($result);
    }

    public function transactionalProviderTest(Request $request, EmailProviderManager $provider, EmailProviderConfigurationService $configuration): JsonResponse
    {
        $data = $request->validate(['email' => 'nullable|email|max:190', 'marketplace_id' => 'nullable|integer']);
        $result = $provider->testConnection($data['email'] ?? null, isset($data['marketplace_id']) ? (int) $data['marketplace_id'] : null);
        $configuration->markTested('transactional', (string) ($result['status'] ?? 'unknown'));
        $status = ($result['status'] ?? 'failed') === 'failed' ? 422 : 200;

        return $this->success($result, $status);
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

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'email-template';
        $slug = $base;
        $suffix = 2;
        while (DB::table('email_templates')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
