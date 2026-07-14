<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampaignExecutionService
{
    public function __construct(
        private EmailTemplateService $templates,
        private EmailTemplateValidator $validator,
        private EmailEligibilityService $eligibility,
        private CampaignAudienceSnapshotService $snapshots,
        private NewsletterAudienceSnapshotService $newsletterSnapshots,
        private EmailPreferenceTokenService $preferenceTokens,
        private RegionalEmailBrandingService $branding,
        private EmailProviderConfigurationService $providerConfiguration,
    ) {}

    public function sendEmailCampaign(int $campaignId, bool $test = false, ?string $testEmail = null): array
    {
        $this->providerConfiguration->apply('marketing');
        $campaign = DB::table('email_campaigns')->find($campaignId);
        if (! $campaign) {
            return $this->result($campaignId, $test, 'Campaign not found.');
        }
        $template = $campaign->email_template_id ? DB::table('email_templates')->find($campaign->email_template_id) : null;
        if (! $template) {
            return $this->result($campaignId, $test, 'An active campaign template is required.');
        }
        $validation = $this->validator->validate($template, true);
        if (! $validation['valid']) {
            return $this->result($campaignId, $test, 'Template validation failed: '.implode(', ', $validation['errors']), ['template_validation' => $validation]);
        }

        if ($test) {
            if (! $this->testRecipientAllowed($testEmail)) {
                return $this->result($campaignId, true, 'Test recipient is not in MARKETING_EMAIL_TEST_RECIPIENTS.');
            }
            $recipients = [$this->testRecipient($testEmail)];
            $snapshot = ['snapshot_id' => null, 'planned' => 1, 'eligible' => 1, 'excluded' => 0];
        } else {
            if (! config('marketing.email.sending_enabled', false)) {
                return $this->result($campaignId, false, 'Production marketing sending is disabled.');
            }
            if (in_array(config('marketing.email.provider', 'sandbox'), ['sandbox', 'log'], true)) {
                return $this->result($campaignId, false, 'A verified production marketing provider is not configured.');
            }
            if (($campaign->requires_approval ?? true) && ! $campaign->approved_at) {
                return $this->result($campaignId, false, 'Campaign approval is required.');
            }
            if (! ($campaign->production_send_enabled ?? false)) {
                return $this->result($campaignId, false, 'This campaign is not enabled for production sending.');
            }
            if (($campaign->paused_at ?? null) || ($campaign->cancelled_at ?? null)) {
                return $this->result($campaignId, false, 'Campaign is paused or cancelled.');
            }
            if (($campaign->reply_to ?? null) && ! filter_var($campaign->reply_to, FILTER_VALIDATE_EMAIL)) {
                return $this->result($campaignId, false, 'Campaign reply-to address is invalid.');
            }
            $sender = $this->branding->context(isset($campaign->marketplace_id) ? (int) $campaign->marketplace_id : null, 'marketing');
            if (! $sender['verified'] || ! $sender['enabled']) {
                return $this->result($campaignId, false, 'The regional marketing sender profile is not verified and enabled.');
            }
            $snapshot = $this->snapshots->freeze($campaignId);
            $recipients = $snapshot['recipients'] ?? [];
        }

        $counts = ['campaign_id' => $campaignId, 'eligible' => count($recipients), 'queued' => 0, 'already_prepared' => 0, 'skipped' => $snapshot['excluded'] ?? 0, 'test_mode' => $test, 'snapshot_id' => $snapshot['snapshot_id'] ?? null, 'snapshot_reused' => $snapshot['reused'] ?? false, 'template_validation' => $validation];
        foreach ($recipients as $recipient) {
            $recipient = (object) $recipient;
            $decision = $test ? ['allowed' => true, 'reasons' => []] : $this->eligibility->marketing($recipient->email, (bool) ($recipient->marketing_opt_in ?? false));
            if (! $decision['allowed']) {
                if (! empty($recipient->recipient_id)) {
                    DB::table('email_campaign_recipients')->where('id', $recipient->recipient_id)->update(['status' => 'excluded', 'eligibility_status' => 'excluded_send_time', 'eligibility_reasons' => json_encode($decision['reasons']), 'updated_at' => now()]);
                }
                $counts['skipped']++;

                continue;
            }
            $key = hash('sha256', 'marketing|'.$campaignId.'|'.($snapshot['snapshot_id'] ?? 'test').'|'.mb_strtolower($recipient->email));
            if (DB::table('email_messages')->where('idempotency_key', $key)->exists()) {
                $counts['already_prepared']++;

                continue;
            }
            $tokens = $this->preferenceTokens->issue($recipient->email, $campaignId);
            $variables = $this->variables($recipient, $tokens, isset($campaign->marketplace_id) ? (int) $campaign->marketplace_id : null);
            $subject = $this->templates->render($template->subject, $variables);
            $html = $this->templates->render($template->html_body, $variables);
            $text = $this->templates->render($template->text_body, $variables);
            $unresolved = $this->validator->unresolved($subject.$html.$text);
            if ($unresolved !== []) {
                $counts['skipped']++;

                continue;
            }
            $recipientId = $recipient->recipient_id ?? DB::table('email_campaign_recipients')->insertGetId([
                'email_campaign_id' => $campaignId, 'email' => $recipient->email, 'status' => 'test_queued', 'eligibility_status' => 'test_only', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('email_messages')->insert([
                'idempotency_key' => $key,
                'email_template_id' => $template->id, 'email_campaign_id' => $campaignId, 'message_type' => $test ? 'marketing_test' : 'marketing',
                'provider' => config('marketing.email.provider', 'sandbox'), 'to_email' => $recipient->email, 'subject' => Str::limit($subject, 190, ''),
                'html_body' => $html, 'text_body' => $text, 'status' => $test ? 'test_queued' : 'queued', 'queue_name' => config('marketing.email.queue', 'marketing'),
                'metadata' => json_encode(['sandbox' => ! config('marketing.email.sending_enabled'), 'test' => $test, 'email_campaign_recipient_id' => $recipientId, 'audience_snapshot_id' => $snapshot['snapshot_id'] ?? null]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('email_campaign_recipients')->where('id', $recipientId)->update(['status' => $test ? 'test_queued' : 'queued', 'updated_at' => now()]);
            $counts['queued']++;
        }
        if (! $test && $counts['queued'] > 0) {
            DB::table('email_campaigns')->where('id', $campaignId)->update(['status' => 'preparing', 'updated_at' => now()]);
        }

        return $counts;
    }

    public function sendNewsletterCampaign(int $campaignId, bool $test = false, ?string $testEmail = null): array
    {
        $this->providerConfiguration->apply('marketing');
        $campaign = DB::table('newsletter_campaigns')->find($campaignId);
        if (! $campaign) {
            return $this->result($campaignId, $test, 'Campaign not found.');
        }
        $template = $campaign->newsletter_template_id ? DB::table('newsletter_templates')->find($campaign->newsletter_template_id) : null;
        if (! $template) {
            return $this->result($campaignId, $test, 'An active newsletter template is required.');
        }
        $validation = $this->validator->validate($template, true);
        if (! $validation['valid']) {
            return $this->result($campaignId, $test, 'Template validation failed: '.implode(', ', $validation['errors']));
        }
        if ($test) {
            if (! $this->testRecipientAllowed($testEmail)) {
                return $this->result($campaignId, true, 'Test recipient is not in MARKETING_EMAIL_TEST_RECIPIENTS.');
            }
            $recipients = [$this->testRecipient($testEmail)];
            $snapshot = ['snapshot_id' => null, 'planned' => 1, 'eligible' => 1, 'excluded' => 0, 'reused' => false];
        } else {
            if (! config('marketing.email.sending_enabled', false)) {
                return $this->result($campaignId, false, 'Production marketing sending is disabled.');
            }
            if (in_array(config('marketing.email.provider', 'sandbox'), ['sandbox', 'log'], true)) {
                return $this->result($campaignId, false, 'A verified production marketing provider is not configured.');
            }
            if (($campaign->requires_approval ?? true) && ! $campaign->approved_at) {
                return $this->result($campaignId, false, 'Newsletter approval is required.');
            }
            if (! ($campaign->production_send_enabled ?? false)) {
                return $this->result($campaignId, false, 'This newsletter is not enabled for production sending.');
            }
            if (($campaign->paused_at ?? null) || ($campaign->cancelled_at ?? null)) {
                return $this->result($campaignId, false, 'Newsletter is paused or cancelled.');
            }
            if (($campaign->reply_to ?? null) && ! filter_var($campaign->reply_to, FILTER_VALIDATE_EMAIL)) {
                return $this->result($campaignId, false, 'Newsletter reply-to address is invalid.');
            }
            $sender = $this->branding->context(isset($campaign->marketplace_id) ? (int) $campaign->marketplace_id : null, 'marketing');
            if (! $sender['verified'] || ! $sender['enabled']) {
                return $this->result($campaignId, false, 'The regional marketing sender profile is not verified and enabled.');
            }
            $snapshot = $this->newsletterSnapshots->freeze($campaignId);
            if (isset($snapshot['error'])) {
                return $this->result($campaignId, false, $snapshot['error']);
            }
            $recipients = $snapshot['recipients'];
        }

        $counts = [
            'campaign_id' => $campaignId,
            'eligible' => count($recipients),
            'queued' => 0,
            'already_prepared' => 0,
            'skipped' => $snapshot['excluded'] ?? 0,
            'test_mode' => $test,
            'snapshot_id' => $snapshot['snapshot_id'] ?? null,
            'snapshot_reused' => $snapshot['reused'] ?? false,
            'template_validation' => $validation,
        ];
        foreach ($recipients as $recipient) {
            $recipient = (object) $recipient;
            $decision = $test ? ['allowed' => true, 'reasons' => []] : $this->eligibility->marketing($recipient->email, ($recipient->consent_status ?? 'unknown') === 'opted_in');
            if (! $decision['allowed']) {
                if (! empty($recipient->recipient_id)) {
                    DB::table('newsletter_campaign_recipients')->where('id', $recipient->recipient_id)->update(['status' => 'excluded', 'eligibility_status' => 'excluded_send_time', 'eligibility_reasons' => json_encode($decision['reasons']), 'updated_at' => now()]);
                }
                $counts['skipped']++;

                continue;
            }
            $key = hash('sha256', 'newsletter|'.$campaignId.'|'.($snapshot['snapshot_id'] ?? 'test').'|'.mb_strtolower($recipient->email));
            if (DB::table('email_messages')->where('idempotency_key', $key)->exists()) {
                $counts['already_prepared']++;

                continue;
            }
            $tokens = $this->preferenceTokens->issue($recipient->email, null);
            $vars = $this->variables($recipient, $tokens, isset($campaign->marketplace_id) ? (int) $campaign->marketplace_id : null);
            $subject = $this->templates->render($campaign->subject ?: $template->subject, $vars);
            $html = $this->templates->render($template->html_body, $vars);
            $text = $this->templates->render($template->text_body, $vars);
            if ($this->validator->unresolved($subject.$html.$text) !== []) {
                $counts['skipped']++;

                continue;
            }
            $recipientId = $recipient->recipient_id ?? DB::table('newsletter_campaign_recipients')->insertGetId([
                'newsletter_campaign_id' => $campaignId,
                'newsletter_subscriber_id' => $recipient->newsletter_subscriber_id ?? null,
                'email' => $recipient->email,
                'status' => 'test_queued',
                'eligibility_status' => 'test_only',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('email_messages')->insert([
                'idempotency_key' => $key,
                'newsletter_campaign_id' => $campaignId,
                'marketplace_id' => $campaign->marketplace_id ?? null,
                'country_id' => $recipient->country_id ?? null,
                'message_type' => $test ? 'newsletter_test' : 'newsletter',
                'provider' => config('marketing.email.provider', 'sandbox'),
                'to_email' => $recipient->email,
                'subject' => Str::limit($subject, 190, ''),
                'html_body' => $html,
                'text_body' => $text,
                'status' => $test ? 'test_queued' : 'queued',
                'queue_name' => config('marketing.email.queue', 'marketing'),
                'metadata' => json_encode(['newsletter_campaign_id' => $campaignId, 'newsletter_campaign_recipient_id' => $recipientId, 'newsletter_audience_snapshot_id' => $snapshot['snapshot_id'] ?? null, 'test' => $test]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('newsletter_campaign_recipients')->where('id', $recipientId)->update(['status' => $test ? 'test_queued' : 'queued', 'updated_at' => now()]);
            $counts['queued']++;
        }
        if (! $test && $counts['queued'] > 0) {
            DB::table('newsletter_campaigns')->where('id', $campaignId)->update(['status' => 'preparing', 'updated_at' => now()]);
        }

        return $counts;
    }

    private function testRecipientAllowed(?string $email): bool
    {
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return in_array(mb_strtolower($email), array_map('mb_strtolower', config('marketing.email.test_recipients', [])), true);
    }

    private function testRecipient(string $email): array
    {
        return ['email' => mb_strtolower($email), 'first_name' => 'Test', 'last_name' => 'Recipient', 'name' => 'Test Recipient', 'marketing_opt_in' => true];
    }

    private function variables(object $recipient, array $tokens, ?int $marketplaceId): array
    {
        $name = trim(($recipient->first_name ?? '').' '.($recipient->last_name ?? '')) ?: ($recipient->name ?? 'Customer');
        $branding = $this->branding->context($marketplaceId, 'marketing');

        return ['first_name' => $recipient->first_name ?? $name, 'contact_name' => $name, 'customer_name' => $name, 'company_name' => $recipient->company_name ?? '', 'email' => $recipient->email, 'country' => $recipient->country_id ?? '', 'country_name' => $recipient->country_name ?? '', 'region' => $recipient->region_id ?? '', 'marketplace_name' => $branding['marketplace_name'], 'marketplace_url' => $branding['base_url'], 'currency' => $branding['currency'], 'unsubscribe_url' => $tokens['unsubscribe_url'], 'preferences_url' => $tokens['preferences_url'], 'current_year' => now()->year];
    }

    private function result(int $campaignId, bool $test, string $error, array $extra = []): array
    {
        return ['campaign_id' => $campaignId, 'eligible' => 0, 'queued' => 0, 'skipped' => 0, 'test_mode' => $test, 'error' => $error] + $extra;
    }
}
