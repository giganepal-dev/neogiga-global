<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Marketing\CampaignExecutionService;
use App\Services\Marketing\CustomerSegmentationService;
use App\Services\Marketing\MarketingAuditLogger;
use App\Services\Marketing\WhatsAppCampaignExecutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketingActionController extends Controller
{
    public function storeSegment(Request $request, CustomerSegmentationService $segments, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:1000'],
            'customer_type' => ['nullable', 'string', 'max:60'],
            'lifecycle_stage' => ['nullable', 'string', 'max:60'],
            'marketing_opt_in' => ['nullable', 'boolean'],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:60'],
        ]);

        $rules = array_filter([
            'customer_type' => $data['customer_type'] ?? null,
            'lifecycle_stage' => $data['lifecycle_stage'] ?? null,
            'marketing_opt_in' => $request->has('marketing_opt_in') ? (bool) $data['marketing_opt_in'] : null,
            'whatsapp_opt_in' => $request->has('whatsapp_opt_in') ? (bool) $data['whatsapp_opt_in'] : null,
            'status' => $data['status'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $segmentId = $segments->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'rules' => $rules,
            'type' => 'dynamic',
            'is_active' => true,
        ]);

        $audit->record($request, 'segment.created', 'customer_segment', $segmentId, ['name' => $data['name'], 'rules' => $rules]);

        return back()->with('status', 'Customer segment created.');
    }

    public function refreshSegment(Request $request, int $segment, CustomerSegmentationService $segments, MarketingAuditLogger $audit): RedirectResponse
    {
        $matched = $segments->refresh($segment);
        $audit->record($request, 'segment.refreshed', 'customer_segment', $segment, ['matched' => $matched]);

        return back()->with('status', "Segment refreshed. {$matched} customers matched.");
    }

    public function storeNewsletterTemplate(Request $request, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'subject' => ['nullable', 'string', 'max:190'],
            'html_body' => ['nullable', 'string', 'max:20000'],
            'text_body' => ['nullable', 'string', 'max:20000'],
        ]);

        $templateId = DB::table('newsletter_templates')->insertGetId([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug('newsletter_templates', $data['name']),
            'subject' => $data['subject'] ?? null,
            'html_body' => $data['html_body'] ?? null,
            'text_body' => $data['text_body'] ?? null,
            'variables' => json_encode(['customer_name', 'unsubscribe_url']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit->record($request, 'newsletter_template.created', 'newsletter_template', $templateId, ['name' => $data['name']]);

        return back()->with('status', 'Newsletter template created.');
    }

    public function storeNewsletterCampaign(Request $request, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'subject' => ['nullable', 'string', 'max:190'],
            'newsletter_template_id' => ['nullable', 'integer'],
            'country_id' => ['nullable', 'integer'],
            'segment_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $campaignId = DB::table('newsletter_campaigns')->insertGetId([
            'name' => $data['name'],
            'subject' => $data['subject'] ?? null,
            'newsletter_template_id' => $data['newsletter_template_id'] ?? null,
            'status' => $data['scheduled_at'] ? 'scheduled' : 'draft',
            'targeting_rules' => json_encode(array_filter([
                'country_id' => $data['country_id'] ?? null,
                'segment_id' => $data['segment_id'] ?? null,
            ])),
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit->record($request, 'newsletter_campaign.created', 'newsletter_campaign', $campaignId, ['name' => $data['name'], 'scheduled_at' => $data['scheduled_at'] ?? null]);

        return back()->with('status', 'Newsletter campaign created.');
    }

    public function storeEmailTemplate(Request $request, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'type' => ['required', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:190'],
            'html_body' => ['nullable', 'string', 'max:20000'],
            'text_body' => ['nullable', 'string', 'max:20000'],
            'is_transactional' => ['nullable', 'boolean'],
        ]);

        $templateId = DB::table('email_templates')->insertGetId([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug('email_templates', $data['name']),
            'type' => $data['type'],
            'subject' => $data['subject'],
            'html_body' => $data['html_body'] ?? null,
            'text_body' => $data['text_body'] ?? null,
            'variables' => json_encode(['customer_name', 'email', 'unsubscribe_url', 'order_number', 'otp_code']),
            'is_transactional' => (bool) ($data['is_transactional'] ?? false),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit->record($request, 'email_template.created', 'email_template', $templateId, ['name' => $data['name'], 'type' => $data['type']]);

        return back()->with('status', 'Email template created.');
    }

    public function storeEmailCampaign(Request $request, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'type' => ['nullable', 'string', 'max:60'],
            'email_template_id' => ['nullable', 'integer'],
            'segment_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $campaignId = DB::table('email_campaigns')->insertGetId([
            'name' => $data['name'],
            'type' => $data['type'] ?? 'marketing',
            'email_template_id' => $data['email_template_id'] ?? null,
            'status' => $data['scheduled_at'] ? 'scheduled' : 'draft',
            'targeting_rules' => json_encode(array_filter(['segment_id' => $data['segment_id'] ?? null])),
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit->record($request, 'email_campaign.created', 'email_campaign', $campaignId, ['name' => $data['name'], 'type' => $data['type'] ?? 'marketing']);

        return back()->with('status', 'Email campaign created in safe mode.');
    }

    public function queueEmailCampaign(Request $request, int $campaign, CampaignExecutionService $campaigns, MarketingAuditLogger $audit): RedirectResponse
    {
        $result = $campaigns->sendEmailCampaign($campaign);
        $audit->record($request, 'email_campaign.queued', 'email_campaign', $campaign, $result);

        return back()->with('status', "Email campaign queued in safe mode. {$result['queued']} queued, {$result['skipped']} skipped.");
    }

    public function sendEmailCampaignTest(Request $request, int $campaign, CampaignExecutionService $campaigns, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);
        $result = $campaigns->sendEmailCampaign($campaign, true, $data['email']);
        $audit->record($request, 'email_campaign.test_queued', 'email_campaign', $campaign, $result);

        return back()->with('status', "Email campaign test queued for {$data['email']} in safe mode.");
    }

    public function queueNewsletterCampaign(Request $request, int $campaign, CampaignExecutionService $campaigns, MarketingAuditLogger $audit): RedirectResponse
    {
        $result = $campaigns->sendNewsletterCampaign($campaign);
        $audit->record($request, 'newsletter_campaign.queued', 'newsletter_campaign', $campaign, $result);

        return back()->with('status', "Newsletter campaign queued in safe mode. {$result['queued']} queued, {$result['skipped']} skipped.");
    }

    public function sendNewsletterCampaignTest(Request $request, int $campaign, CampaignExecutionService $campaigns, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);
        $result = $campaigns->sendNewsletterCampaign($campaign, true, $data['email']);
        $audit->record($request, 'newsletter_campaign.test_queued', 'newsletter_campaign', $campaign, $result);

        return back()->with('status', "Newsletter campaign test queued for {$data['email']} in safe mode.");
    }

    public function storeWhatsappTemplate(Request $request, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'provider_template_name' => ['nullable', 'string', 'max:190'],
            'body' => ['nullable', 'string', 'max:4000'],
        ]);

        $templateId = DB::table('whatsapp_templates')->insertGetId([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug('whatsapp_templates', $data['name']),
            'provider_template_name' => $data['provider_template_name'] ?? null,
            'approval_status' => 'draft',
            'body' => $data['body'] ?? null,
            'variables' => json_encode(['customer_name', 'order_number', 'unsubscribe_url']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit->record($request, 'whatsapp_template.created', 'whatsapp_template', $templateId, ['name' => $data['name']]);

        return back()->with('status', 'WhatsApp template placeholder created.');
    }

    public function storeWhatsappCampaign(Request $request, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'whatsapp_template_id' => ['nullable', 'integer'],
            'segment_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $campaignId = DB::table('whatsapp_campaigns')->insertGetId([
            'name' => $data['name'],
            'whatsapp_template_id' => $data['whatsapp_template_id'] ?? null,
            'status' => $data['scheduled_at'] ? 'scheduled' : 'draft',
            'targeting_rules' => json_encode(array_filter(['segment_id' => $data['segment_id'] ?? null])),
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit->record($request, 'whatsapp_campaign.created', 'whatsapp_campaign', $campaignId, ['name' => $data['name']]);

        return back()->with('status', 'WhatsApp campaign created in placeholder mode.');
    }

    public function queueWhatsappCampaign(Request $request, int $campaign, WhatsAppCampaignExecutionService $campaigns, MarketingAuditLogger $audit): RedirectResponse
    {
        $result = $campaigns->queueCampaign($campaign);
        $audit->record($request, 'whatsapp_campaign.queued', 'whatsapp_campaign', $campaign, $result);

        return back()->with('status', "WhatsApp campaign queued for manual export. {$result['queued']} queued, {$result['skipped']} skipped.");
    }

    public function sendWhatsappCampaignTest(Request $request, int $campaign, WhatsAppCampaignExecutionService $campaigns, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:60']]);
        $result = $campaigns->queueCampaign($campaign, true, $data['phone']);
        $audit->record($request, 'whatsapp_campaign.test_queued', 'whatsapp_campaign', $campaign, $result);

        return back()->with('status', "WhatsApp campaign test queued for {$data['phone']} in manual export mode.");
    }

    public function updateMarketingSettings(Request $request, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'email_provider' => ['nullable', 'string', 'max:80'],
            'whatsapp_provider' => ['nullable', 'string', 'max:80'],
            'newsletter_double_opt_in' => ['nullable', 'boolean'],
            'abandoned_cart_first_reminder_minutes' => ['nullable', 'integer', 'min:15', 'max:10080'],
            'campaign_daily_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'ga_measurement_id' => ['nullable', 'string', 'max:80'],
        ]);

        foreach ($data as $key => $value) {
            $table = $key === 'ga_measurement_id' ? 'analytics_settings' : 'marketing_settings';
            DB::table($table)->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value), 'group' => $table === 'analytics_settings' ? 'analytics' : 'marketing', 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $audit->record($request, 'settings.updated', 'marketing_settings', null, ['keys' => array_keys($data)]);

        return back()->with('status', 'Settings saved. Provider credentials still come from environment variables.');
    }

    private function uniqueSlug(string $table, string $name): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $i = 2;
        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
