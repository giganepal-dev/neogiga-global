<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Marketing\SendEmailCampaignJob;
use App\Jobs\Marketing\SendNewsletterCampaignJob;
use App\Services\Marketing\CampaignExecutionService;
use App\Services\Marketing\CustomerSegmentationService;
use App\Services\Marketing\EmailProviderConfigurationService;
use App\Services\Marketing\EmailProviderManager;
use App\Services\Marketing\EmailTemplateValidator;
use App\Services\Marketing\MarketingAuditLogger;
use App\Services\Marketing\MarketingEmailProviderManager;
use App\Services\Marketing\RegionalEmailBrandingService;
use App\Services\Marketing\WhatsAppCampaignExecutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class MarketingActionController extends Controller
{
    public function storeSegment(Request $request, CustomerSegmentationService $segments, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:1000'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'customer_type' => ['nullable', 'string', 'max:60'],
            'lifecycle_stage' => ['nullable', 'string', 'max:60'],
            'marketing_opt_in' => ['nullable', 'boolean'],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:60'],
            'marketing_eligible' => ['nullable', 'boolean'],
        ]);

        $rules = array_filter([
            'country_id' => $data['country_id'] ?? null,
            'customer_type' => $data['customer_type'] ?? null,
            'lifecycle_stage' => $data['lifecycle_stage'] ?? null,
            'marketing_opt_in' => $request->has('marketing_opt_in') ? (bool) $data['marketing_opt_in'] : null,
            'whatsapp_opt_in' => $request->has('whatsapp_opt_in') ? (bool) $data['whatsapp_opt_in'] : null,
            'status' => $data['status'] ?? null,
            'marketing_eligible' => $request->has('marketing_eligible') ? (bool) $data['marketing_eligible'] : null,
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
            'subject' => ['required', 'string', 'max:190'],
            'html_body' => ['nullable', 'string', 'max:20000'],
            'text_body' => ['nullable', 'string', 'max:20000'],
        ]);

        $validation = app(EmailTemplateValidator::class)->validate($data, true);
        if (! $validation['valid']) {
            return back()->withInput()->with('error', 'Template validation failed: '.implode(', ', $validation['errors']));
        }
        $templateId = DB::transaction(function () use ($data, $validation, $request): int {
            $templateId = DB::table('newsletter_templates')->insertGetId([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug('newsletter_templates', $data['name']),
                'subject' => $data['subject'],
                'html_body' => $data['html_body'] ?? null,
                'text_body' => $data['text_body'] ?? null,
                'variables' => json_encode($validation['variables']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('newsletter_template_versions')->insert([
                'newsletter_template_id' => $templateId, 'version' => 1, 'subject' => $data['subject'],
                'html_body' => $data['html_body'] ?? null, 'text_body' => $data['text_body'] ?? null,
                'variables' => json_encode($validation['variables']), 'created_by' => $request->user()?->id,
                'validation_results' => json_encode($validation), 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $templateId;
        });

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
            'marketplace_id' => ['nullable', 'integer'],
            'reply_to' => ['nullable', 'email', 'max:190'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $campaignId = DB::table('newsletter_campaigns')->insertGetId([
            'name' => $data['name'],
            'subject' => $data['subject'] ?? null,
            'newsletter_template_id' => $data['newsletter_template_id'] ?? null,
            'status' => 'draft',
            'targeting_rules' => json_encode(array_filter([
                'country_id' => $data['country_id'] ?? null,
                'segment_id' => $data['segment_id'] ?? null,
            ])),
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'reply_to' => $data['reply_to'] ?? null,
            'internal_reference' => 'NEWS-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
            'requires_approval' => true,
            'production_send_enabled' => false,
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

        $isTransactional = (bool) ($data['is_transactional'] ?? false);
        $validation = app(EmailTemplateValidator::class)->validate($data, ! $isTransactional);
        if (! $validation['valid']) {
            return back()->withInput()->with('error', 'Template validation failed: '.implode(', ', $validation['errors']));
        }
        $templateId = DB::transaction(function () use ($data, $isTransactional, $validation): int {
            $templateId = DB::table('email_templates')->insertGetId([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug('email_templates', $data['name']),
                'type' => $data['type'],
                'subject' => $data['subject'],
                'html_body' => $data['html_body'] ?? null,
                'text_body' => $data['text_body'] ?? null,
                'variables' => json_encode($validation['variables']),
                'is_transactional' => $isTransactional,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('email_template_versions')->insert([
                'email_template_id' => $templateId, 'version' => 1, 'subject' => $data['subject'],
                'html_body' => $data['html_body'] ?? null, 'text_body' => $data['text_body'] ?? null,
                'variables' => json_encode($validation['variables']), 'validation_results' => json_encode($validation),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return $templateId;
        });

        $audit->record($request, 'email_template.created', 'email_template', $templateId, ['name' => $data['name'], 'type' => $data['type']]);

        return back()->with('status', 'Email template created.');
    }

    public function storeEmailCampaign(Request $request, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'type' => ['nullable', 'string', 'max:60'],
            'email_template_id' => ['required', 'integer', 'exists:email_templates,id'],
            'segment_id' => ['nullable', 'integer', 'exists:customer_segments,id'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $campaignId = DB::table('email_campaigns')->insertGetId([
            'name' => $data['name'],
            'type' => $data['type'] ?? 'marketing',
            'email_template_id' => $data['email_template_id'] ?? null,
            'status' => 'draft',
            'targeting_rules' => json_encode(array_filter(['segment_id' => $data['segment_id'] ?? null])),
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'internal_reference' => 'CAM-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
            'requires_approval' => true,
            'production_send_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit->record($request, 'email_campaign.created', 'email_campaign', $campaignId, ['name' => $data['name'], 'type' => $data['type'] ?? 'marketing']);

        return back()->with('status', 'Email campaign created in safe mode.');
    }

    public function queueEmailCampaign(Request $request, int $campaign, CampaignExecutionService $campaigns, MarketingAuditLogger $audit): RedirectResponse
    {
        $row = DB::table('email_campaigns')->find($campaign);
        if (! $row) {
            return back()->with('error', 'Campaign not found.');
        }
        if (! $row->approved_at) {
            return back()->with('error', 'Approve the campaign before preparing its audience.');
        }
        if ($row->scheduled_at && now()->lt($row->scheduled_at)) {
            DB::table('email_campaigns')->where('id', $campaign)->update(['status' => 'scheduled', 'updated_at' => now()]);
            $audit->record($request, 'email_campaign.scheduled', 'email_campaign', $campaign, ['scheduled_at' => $row->scheduled_at]);

            return back()->with('status', 'Campaign scheduled. The campaign-preparation worker will claim it at the selected time.');
        }
        SendEmailCampaignJob::dispatch(['campaign_id' => $campaign]);
        $audit->record($request, 'email_campaign.queued', 'email_campaign', $campaign, ['queue' => config('marketing.email.queue')]);

        return back()->with('status', 'Campaign audience preparation queued. All production gates will be rechecked by the worker.');
    }

    public function sendEmailCampaignTest(Request $request, int $campaign, CampaignExecutionService $campaigns, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);
        SendEmailCampaignJob::dispatch(['campaign_id' => $campaign, 'test' => true, 'test_email' => mb_strtolower($data['email'])]);
        $audit->record($request, 'email_campaign.test_queued', 'email_campaign', $campaign, ['test_recipient_hash' => hash('sha256', mb_strtolower($data['email']))]);

        return back()->with('status', "Email campaign test queued for {$data['email']} in safe mode.");
    }

    public function approveEmailCampaign(
        Request $request,
        int $campaign,
        EmailTemplateValidator $validator,
        MarketingAuditLogger $audit,
        EmailProviderConfigurationService $providers,
        RegionalEmailBrandingService $branding,
    ): RedirectResponse {
        $row = DB::table('email_campaigns')->find($campaign);
        if (! $row) {
            return back()->with('error', 'Campaign not found.');
        }
        $template = $row->email_template_id ? DB::table('email_templates')->find($row->email_template_id) : null;
        if (! $template) {
            return back()->with('error', 'An active template is required.');
        }
        $validation = $validator->validate($template, true);
        if (! $validation['valid']) {
            return back()->with('error', 'Template validation failed: '.implode(', ', $validation['errors']));
        }
        $productionEnabled = $request->boolean('production_send_enabled');
        $providers->apply('marketing');
        if ($productionEnabled) {
            if (! config('marketing.email.sending_enabled', false) || in_array(config('marketing.email.provider'), ['sandbox', 'log'], true)) {
                return back()->with('error', 'Enable a production SMTP or API provider outside test mode before approving a live campaign.');
            }
            $sender = $branding->context($row->marketplace_id ? (int) $row->marketplace_id : null, 'marketing');
            if (! $sender['verified'] || ! $sender['enabled']) {
                return back()->with('error', 'A verified and enabled marketing sender profile is required for live campaign approval.');
            }
        }
        DB::table('email_campaigns')->where('id', $campaign)->update(['status' => 'approved', 'approved_by' => $request->user()?->id, 'approved_at' => now(), 'production_send_enabled' => $productionEnabled, 'updated_at' => now()]);
        $audit->record($request, 'email_campaign.approved', 'email_campaign', $campaign, ['production_send_enabled' => $productionEnabled, 'validation' => $validation]);

        return back()->with('status', $productionEnabled ? 'Campaign approved for production sending. Audience and delivery gates remain enforced.' : 'Campaign approved for audience preparation. Production sending remains disabled.');
    }

    public function pauseEmailCampaign(Request $request, int $campaign, MarketingAuditLogger $audit): RedirectResponse
    {
        DB::table('email_campaigns')->where('id', $campaign)->whereNull('cancelled_at')->update(['status' => 'paused', 'paused_at' => now(), 'updated_at' => now()]);
        $audit->record($request, 'email_campaign.paused', 'email_campaign', $campaign);

        return back()->with('status', 'Campaign paused.');
    }

    public function resumeEmailCampaign(Request $request, int $campaign, MarketingAuditLogger $audit): RedirectResponse
    {
        $row = DB::table('email_campaigns')->find($campaign);
        if (! $row || $row->cancelled_at) {
            return back()->with('error', 'Campaign not found or cancelled.');
        }
        if (! $row->approved_at) {
            return back()->with('error', 'Approval is required before resume.');
        }
        DB::table('email_campaigns')->where('id', $campaign)->update(['status' => $row->scheduled_at ? 'scheduled' : 'approved', 'paused_at' => null, 'updated_at' => now()]);
        $audit->record($request, 'email_campaign.resumed', 'email_campaign', $campaign);

        return back()->with('status', 'Campaign resumed without changing its frozen audience.');
    }

    public function cancelEmailCampaign(Request $request, int $campaign, MarketingAuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($campaign): void {
            DB::table('email_campaigns')->where('id', $campaign)->whereNull('cancelled_at')->update(['status' => 'cancelled', 'cancelled_at' => now(), 'production_send_enabled' => false, 'updated_at' => now()]);
            DB::table('email_messages')->where('email_campaign_id', $campaign)->whereIn('status', ['queued', 'scheduled'])->update(['status' => 'cancelled', 'updated_at' => now()]);
        });
        $audit->record($request, 'email_campaign.cancelled', 'email_campaign', $campaign);

        return back()->with('status', 'Campaign cancelled.');
    }

    public function queueNewsletterCampaign(Request $request, int $campaign, MarketingAuditLogger $audit): RedirectResponse
    {
        SendNewsletterCampaignJob::dispatch(['campaign_id' => $campaign]);
        $audit->record($request, 'newsletter_campaign.queued', 'newsletter_campaign', $campaign, ['queue' => config('marketing.email.queue')]);

        return back()->with('status', 'Newsletter audience preparation queued. Production gates will be rechecked by the worker.');
    }

    public function sendNewsletterCampaignTest(Request $request, int $campaign, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);
        SendNewsletterCampaignJob::dispatch(['campaign_id' => $campaign, 'test' => true, 'test_email' => mb_strtolower($data['email'])]);
        $audit->record($request, 'newsletter_campaign.test_queued', 'newsletter_campaign', $campaign, ['test_recipient_hash' => hash('sha256', mb_strtolower($data['email']))]);

        return back()->with('status', "Newsletter campaign test queued for {$data['email']} in safe mode.");
    }

    public function approveNewsletterCampaign(Request $request, int $campaign, EmailTemplateValidator $validator, MarketingAuditLogger $audit): RedirectResponse
    {
        $row = DB::table('newsletter_campaigns')->find($campaign);
        if (! $row) {
            return back()->with('error', 'Newsletter campaign not found.');
        }
        $template = $row->newsletter_template_id ? DB::table('newsletter_templates')->find($row->newsletter_template_id) : null;
        if (! $template) {
            return back()->with('error', 'An active newsletter template is required.');
        }
        $validation = $validator->validate($template, true);
        if (! $validation['valid']) {
            return back()->with('error', 'Template validation failed: '.implode(', ', $validation['errors']));
        }
        DB::table('newsletter_campaigns')->where('id', $campaign)->update(['status' => 'approved', 'approved_by' => $request->user()?->id, 'approved_at' => now(), 'production_send_enabled' => false, 'updated_at' => now()]);
        $audit->record($request, 'newsletter_campaign.approved', 'newsletter_campaign', $campaign, ['production_send_enabled' => false, 'validation' => $validation]);

        return back()->with('status', 'Newsletter approved for audience preparation. Production sending remains disabled.');
    }

    public function pauseNewsletterCampaign(Request $request, int $campaign, MarketingAuditLogger $audit): RedirectResponse
    {
        DB::table('newsletter_campaigns')->where('id', $campaign)->whereNull('cancelled_at')->update(['status' => 'paused', 'paused_at' => now(), 'updated_at' => now()]);
        $audit->record($request, 'newsletter_campaign.paused', 'newsletter_campaign', $campaign);

        return back()->with('status', 'Newsletter paused.');
    }

    public function resumeNewsletterCampaign(Request $request, int $campaign, MarketingAuditLogger $audit): RedirectResponse
    {
        $row = DB::table('newsletter_campaigns')->find($campaign);
        if (! $row || $row->cancelled_at) {
            return back()->with('error', 'Newsletter campaign not found or cancelled.');
        }
        if (! $row->approved_at) {
            return back()->with('error', 'Approval is required before resume.');
        }
        DB::table('newsletter_campaigns')->where('id', $campaign)->update(['status' => $row->scheduled_at ? 'scheduled' : 'approved', 'paused_at' => null, 'updated_at' => now()]);
        $audit->record($request, 'newsletter_campaign.resumed', 'newsletter_campaign', $campaign);

        return back()->with('status', 'Newsletter resumed without changing its frozen audience.');
    }

    public function cancelNewsletterCampaign(Request $request, int $campaign, MarketingAuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($campaign): void {
            DB::table('newsletter_campaigns')->where('id', $campaign)->whereNull('cancelled_at')->update(['status' => 'cancelled', 'cancelled_at' => now(), 'production_send_enabled' => false, 'updated_at' => now()]);
            DB::table('email_messages')->where('newsletter_campaign_id', $campaign)->whereIn('status', ['queued', 'scheduled'])->update(['status' => 'cancelled', 'updated_at' => now()]);
        });
        $audit->record($request, 'newsletter_campaign.cancelled', 'newsletter_campaign', $campaign);

        return back()->with('status', 'Newsletter cancelled.');
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

        return back()->with('status', 'Operational settings saved.');
    }

    public function updateEmailProvider(
        Request $request,
        EmailProviderConfigurationService $providers,
        MarketingAuditLogger $audit,
    ): RedirectResponse {
        $data = $request->validate([
            'channel' => ['required', 'string', 'in:marketing,transactional'],
            'transport' => ['required', 'string', 'in:sandbox,log,smtp,generic_http'],
            'smtp_host' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9.-]+$/i'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
            'smtp_local_domain' => ['nullable', 'string', 'max:255'],
            'smtp_username' => ['nullable', 'string', 'max:500'],
            'smtp_password' => ['nullable', 'string', 'max:2000'],
            'api_base_url' => ['nullable', 'url:https', 'max:2048'],
            'account_id' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:4000'],
            'webhook_secret' => ['nullable', 'string', 'max:4000'],
            'sender_profile_id' => ['nullable', 'integer', 'exists:email_sender_profiles,id'],
            'sending_domain' => ['nullable', 'string', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:190'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:10000'],
            'daily_limit' => ['required', 'integer', 'min:1', 'max:1000000'],
            'timeout' => ['required', 'integer', 'min:2', 'max:120'],
            'test_recipients' => ['nullable', 'string', 'max:10000'],
            'test_recipient' => ['nullable', 'email', 'max:190'],
            'clear_credentials' => ['nullable', 'boolean'],
        ]);
        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['test_mode'] = $request->boolean('test_mode');
        $data['clear_credentials'] = $request->boolean('clear_credentials');

        try {
            $summary = $providers->save($data['channel'], $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $audit->record($request, 'email_provider.updated', 'email_provider_config', null, [
            'channel' => $summary['channel'],
            'transport' => $summary['transport'],
            'is_enabled' => $summary['is_enabled'],
            'test_mode' => $summary['test_mode'],
        ]);

        return back()->with('status', ucfirst($data['channel']).' email provider saved with encrypted credentials. Restart long-running queue workers after changing transport settings.');
    }

    public function testMarketingProvider(
        Request $request,
        MarketingEmailProviderManager $provider,
        EmailProviderConfigurationService $configuration,
        MarketingAuditLogger $audit,
    ): RedirectResponse {
        try {
            $result = $provider->testConnection();
            $status = (string) ($result['status'] ?? 'unknown');
            $configuration->markTested('marketing', $status);
            $audit->record($request, 'email_provider.tested', 'email_provider_config', null, ['channel' => 'marketing', 'status' => $status]);

            return back()->with('status', 'Marketing provider test: '.$status.'.');
        } catch (Throwable $exception) {
            $configuration->markTested('marketing', 'failed');

            return back()->with('error', 'Marketing provider test failed: '.$exception->getMessage());
        }
    }

    public function testTransactionalProvider(
        Request $request,
        EmailProviderManager $provider,
        EmailProviderConfigurationService $configuration,
        MarketingAuditLogger $audit,
    ): RedirectResponse {
        $data = $request->validate(['email' => ['nullable', 'email', 'max:190']]);
        try {
            $result = $provider->testConnection($data['email'] ?? null);
            $status = (string) ($result['status'] ?? 'unknown');
            $configuration->markTested('transactional', $status);
            $audit->record($request, 'email_provider.tested', 'email_provider_config', null, ['channel' => 'transactional', 'status' => $status]);

            return back()->with('status', 'Transactional provider test: '.$status.'.');
        } catch (Throwable $exception) {
            $configuration->markTested('transactional', 'failed');

            return back()->with('error', 'Transactional provider test failed: '.$exception->getMessage());
        }
    }

    public function updateEmailSenderProfile(Request $request, int $sender, MarketingAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'from_name' => ['required', 'string', 'max:190'],
            'from_email' => ['required', 'email', 'max:190'],
            'reply_to' => ['nullable', 'email', 'max:190'],
            'domain' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9.-]+$/i'],
            'base_url' => ['required', 'url:http,https', 'max:2048'],
        ]);
        $fromDomain = mb_strtolower((string) str($data['from_email'])->afterLast('@'));
        if (! hash_equals(mb_strtolower($data['domain']), $fromDomain)) {
            return back()->withInput()->with('error', 'The sender email must use the configured sender domain.');
        }
        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['is_verified'] = $request->boolean('is_verified');
        if (! DB::table('email_sender_profiles')->where('id', $sender)->exists()) {
            return back()->with('error', 'Sender profile not found.');
        }
        DB::table('email_sender_profiles')->where('id', $sender)->update($data + ['updated_at' => now()]);
        $audit->record($request, 'email_sender.updated', 'email_sender_profile', $sender, [
            'domain' => $data['domain'],
            'is_enabled' => $data['is_enabled'],
            'is_verified' => $data['is_verified'],
        ]);

        return back()->with('status', 'Sender profile updated. Verification confirmation was recorded in the audit log.');
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
