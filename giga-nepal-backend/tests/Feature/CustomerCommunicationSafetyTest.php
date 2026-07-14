<?php

namespace Tests\Feature;

use App\Jobs\Marketing\SendMarketingEmailBatchJob;
use App\Jobs\Marketing\SendNewsletterEmailBatchJob;
use App\Services\Marketing\CampaignExecutionService;
use App\Services\Marketing\EmailEligibilityService;
use App\Services\Marketing\EmailPreferenceTokenService;
use App\Services\Marketing\EmailWebhookService;
use App\Services\Marketing\MarketingEmailProviderManager;
use App\Services\Marketing\OrderNotificationService;
use App\Services\Marketing\RegionalEmailBrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerCommunicationSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsubscribe_requires_confirmation_and_does_not_block_transactional_email(): void
    {
        $email = 'buyer@example.test';
        DB::table('customer_profiles')->insert(['email' => $email, 'status' => 'active', 'marketing_opt_in' => true, 'marketing_status' => 'opted_in', 'transactional_eligible' => true, 'created_at' => now(), 'updated_at' => now()]);
        $token = app(EmailPreferenceTokenService::class)->issue($email, 25)['token'];

        $this->get('/email/unsubscribe/'.$token)->assertOk()->assertSee('Marketing email unsubscribe');
        $this->assertNull(DB::table('unsubscribes')->where('token_hash', hash('sha256', $token))->value('unsubscribed_at'));
        $this->post('/email/unsubscribe/'.$token, ['confirmation' => '1', 'reason' => 'Not relevant'])->assertRedirect();

        $this->assertDatabaseHas('unsubscribes', ['email' => $email, 'email_campaign_id' => 25, 'scope' => 'all_marketing']);
        $this->assertNotNull(DB::table('unsubscribes')->where('email', $email)->value('confirmed_at'));
        $eligibility = app(EmailEligibilityService::class);
        $this->assertFalse($eligibility->marketing($email, true)['allowed']);
        $this->assertTrue($eligibility->transactional($email)['allowed']);

        app(OrderNotificationService::class)->orderStatus($email, 'NG-1001', 'confirmed', 1001);
        $this->assertDatabaseHas('email_messages', ['to_email' => $email, 'message_type' => 'transactional', 'status' => 'test_queued', 'queue_name' => 'transactional']);
    }

    public function test_signed_webhooks_are_idempotent_and_apply_hard_bounce_and_complaint_suppression(): void
    {
        config(['marketing.webhooks.secret' => 'webhook-test-secret']);
        $contactId = DB::table('customer_contacts')->insertGetId($this->contactRow('Webhook Buyer'));
        DB::table('contact_email_addresses')->insert($this->emailRow($contactId, 'bounce@example.test'));
        $messageId = DB::table('email_messages')->insertGetId(['idempotency_key' => hash('sha256', 'webhook-message'), 'message_type' => 'transactional', 'provider' => 'sandbox', 'provider_message_id' => 'provider-message-1', 'to_email' => 'bounce@example.test', 'subject' => 'Test', 'status' => 'sent', 'created_at' => now(), 'updated_at' => now()]);
        $raw = json_encode(['id' => 'provider-event-1', 'event' => 'hard_bounce', 'message_id' => 'provider-message-1', 'email' => 'bounce@example.test', 'reason' => 'invalid mailbox']);
        $signature = hash_hmac('sha256', $raw, 'webhook-test-secret');
        $webhooks = app(EmailWebhookService::class);
        $first = $webhooks->ingest('sandbox', $raw, $signature);
        $second = $webhooks->ingest('sandbox', $raw, $signature);
        $this->assertSame($first['event_ids'], $second['event_ids']);
        $this->assertSame(1, DB::table('email_webhook_events')->count());
        $webhooks->process($first['event_ids'][0]);
        $this->assertDatabaseHas('email_messages', ['id' => $messageId, 'status' => 'hard_bounce']);
        $this->assertDatabaseHas('suppression_lists', ['email' => 'bounce@example.test', 'reason_code' => 'hard_bounce', 'is_global' => true, 'is_active' => true]);
        $this->assertDatabaseHas('contact_email_addresses', ['normalized_email' => 'bounce@example.test', 'status' => 'hard_bounced', 'is_valid' => false]);

        $complaintRaw = json_encode(['id' => 'provider-event-2', 'event' => 'complaint', 'email' => 'complaint@example.test']);
        $complaint = $webhooks->ingest('sandbox', $complaintRaw, hash_hmac('sha256', $complaintRaw, 'webhook-test-secret'));
        $webhooks->process($complaint['event_ids'][0]);
        $this->assertDatabaseHas('suppression_lists', ['email' => 'complaint@example.test', 'reason_code' => 'complaint', 'is_global' => true]);
        $this->assertSame(1, DB::table('email_complaints')->count());
    }

    public function test_webhook_endpoint_rejects_invalid_signatures(): void
    {
        config(['marketing.webhooks.secret' => 'webhook-test-secret']);
        Queue::fake();
        $this->postJson('/api/v1/email/webhooks/sandbox', ['id' => 'bad-1', 'event' => 'delivered'], ['X-NeoGiga-Signature' => 'invalid'])->assertUnauthorized();
        $this->assertSame(0, DB::table('email_webhook_events')->count());
    }

    public function test_campaign_uses_frozen_country_audience_and_cannot_duplicate_prepared_messages(): void
    {
        config(['marketing.email.sending_enabled' => true, 'marketing.email.provider' => 'configured-provider', 'marketing.email.test_recipients' => ['safe-test@example.test']]);
        $countryId = DB::table('countries')->insertGetId(['name' => 'Sri Lanka', 'iso_code_2' => 'LK', 'iso_code_3' => 'LKA', 'currency_code' => 'LKR', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $marketplaceId = $this->marketplace('INDIA');
        DB::table('email_sender_profiles')->insert(['marketplace_id' => $marketplaceId, 'name' => 'NeoGiga India Marketing Test', 'purpose' => 'marketing', 'from_name' => 'NeoGiga India', 'from_email' => 'news@news.neogiga.in', 'reply_to' => 'support@neogiga.in', 'domain' => 'news.neogiga.in', 'base_url' => 'https://neogiga.in', 'default_currency' => 'INR', 'default_language' => 'en', 'is_verified' => true, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('customer_profiles')->insert([
            ['first_name' => 'Eligible', 'email' => 'eligible@example.test', 'country_id' => $countryId, 'marketplace_id' => $marketplaceId, 'status' => 'active', 'marketing_opt_in' => true, 'marketing_status' => 'opted_in', 'created_at' => now(), 'updated_at' => now()],
            ['first_name' => 'Transactional', 'email' => 'transactional@example.test', 'country_id' => $countryId, 'marketplace_id' => $marketplaceId, 'status' => 'active', 'marketing_opt_in' => false, 'marketing_status' => 'transactional_only', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $templateId = DB::table('email_templates')->insertGetId(['name' => 'Regional campaign', 'slug' => 'regional-campaign', 'type' => 'regional_offer', 'subject' => 'NeoGiga regional update', 'html_body' => '<p>NeoGiga update</p><a href="{{marketplace_url}}">Shop</a><a href="{{unsubscribe_url}}">Unsubscribe</a><a href="{{preferences_url}}">Preferences</a>', 'text_body' => 'NeoGiga {{marketplace_url}} {{unsubscribe_url}} {{preferences_url}}', 'is_transactional' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $campaignId = DB::table('email_campaigns')->insertGetId(['email_template_id' => $templateId, 'name' => 'Sri Lanka engineers', 'type' => 'marketing', 'status' => 'approved', 'marketplace_id' => $marketplaceId, 'target_country_ids' => json_encode([$countryId]), 'requires_approval' => true, 'approved_at' => now(), 'production_send_enabled' => true, 'created_at' => now(), 'updated_at' => now()]);

        $campaigns = app(CampaignExecutionService::class);
        $first = $campaigns->sendEmailCampaign($campaignId);
        $second = $campaigns->sendEmailCampaign($campaignId);
        $this->assertSame(1, $first['queued']);
        $this->assertSame(1, $first['skipped']);
        $this->assertTrue($second['snapshot_reused']);
        $this->assertSame(1, $second['already_prepared']);
        $this->assertSame(1, DB::table('campaign_audience_snapshots')->where('email_campaign_id', $campaignId)->count());
        $this->assertSame(1, DB::table('email_messages')->where('email_campaign_id', $campaignId)->count());
        $this->assertStringContainsString('https://neogiga.in', DB::table('email_messages')->where('email_campaign_id', $campaignId)->value('html_body'));
        $this->assertDatabaseHas('email_campaign_recipients', ['email_campaign_id' => $campaignId, 'email' => 'transactional@example.test', 'eligibility_status' => 'excluded']);
    }

    public function test_campaign_tests_are_allowlisted_and_invalid_variables_block_delivery(): void
    {
        config(['marketing.email.test_recipients' => ['safe-test@example.test']]);
        $templateId = DB::table('email_templates')->insertGetId(['name' => 'Bad variables', 'slug' => 'bad-variables', 'type' => 'marketing', 'subject' => 'NeoGiga {{not_allowed}}', 'html_body' => 'NeoGiga <a href="{{unsubscribe_url}}">unsubscribe</a> <a href="{{preferences_url}}">preferences</a>', 'text_body' => 'NeoGiga {{unsubscribe_url}} {{preferences_url}}', 'is_transactional' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $campaignId = DB::table('email_campaigns')->insertGetId(['email_template_id' => $templateId, 'name' => 'Bad campaign', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
        $campaigns = app(CampaignExecutionService::class);
        $badVariable = $campaigns->sendEmailCampaign($campaignId, true, 'safe-test@example.test');
        $this->assertStringContainsString('Template validation failed', $badVariable['error']);

        DB::table('email_templates')->where('id', $templateId)->update(['subject' => 'NeoGiga test', 'updated_at' => now()]);
        $denied = $campaigns->sendEmailCampaign($campaignId, true, 'other@example.test');
        $this->assertStringContainsString('not in MARKETING_EMAIL_TEST_RECIPIENTS', $denied['error']);
        $allowed = $campaigns->sendEmailCampaign($campaignId, true, 'safe-test@example.test');
        $this->assertSame(1, $allowed['queued']);
        $this->assertSame(1, DB::table('email_messages')->where('message_type', 'marketing_test')->count());
    }

    public function test_marketing_batches_respect_rate_pause_resume_and_provider_message_ids(): void
    {
        config([
            'marketing.email.provider' => 'generic_http',
            'marketing.email.api_base_url' => 'https://provider.example.test',
            'marketing.email.api_key' => 'test-provider-key',
            'marketing.email.sending_enabled' => true,
            'marketing.email.rate_limit_per_minute' => 1,
            'marketing.email.daily_limit' => 10,
        ]);
        Queue::fake();
        Http::fake(function ($request) {
            $messages = collect($request->data()['messages'] ?? [])->map(fn ($message) => [
                'client_reference' => $message['client_reference'],
                'id' => 'provider-message-'.$message['client_reference'],
            ])->all();

            return Http::response(['status' => 'accepted', 'batch_id' => 'provider-batch', 'messages' => $messages], 202);
        });

        $marketplaceId = $this->marketplace('INDIA');
        DB::table('email_sender_profiles')->insert([
            'marketplace_id' => $marketplaceId, 'name' => 'India Marketing Batch Test', 'purpose' => 'marketing',
            'from_name' => 'NeoGiga India', 'from_email' => 'news@news.neogiga.in', 'reply_to' => 'support@neogiga.in',
            'domain' => 'news.neogiga.in', 'base_url' => 'https://neogiga.in', 'default_currency' => 'INR',
            'default_language' => 'en', 'is_verified' => true, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $campaignId = DB::table('email_campaigns')->insertGetId([
            'name' => 'Rate limited batch', 'type' => 'marketing', 'status' => 'approved', 'marketplace_id' => $marketplaceId,
            'approved_at' => now(), 'production_send_enabled' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $messageIds = collect(['one@example.test', 'two@example.test'])->map(fn ($email) => DB::table('email_messages')->insertGetId([
            'idempotency_key' => hash('sha256', $email), 'email_campaign_id' => $campaignId, 'marketplace_id' => $marketplaceId,
            'message_type' => 'marketing', 'queue_name' => 'marketing', 'provider' => 'generic_http', 'to_email' => $email,
            'subject' => 'NeoGiga update', 'html_body' => '<p>Update</p>', 'text_body' => 'Update', 'status' => 'queued',
            'metadata' => json_encode(['email_campaign_recipient_id' => 1]), 'created_at' => now(), 'updated_at' => now(),
        ]));

        $job = new SendMarketingEmailBatchJob($campaignId);
        $job->handle(app(MarketingEmailProviderManager::class), app(RegionalEmailBrandingService::class));
        $this->assertDatabaseHas('email_messages', ['id' => $messageIds[0], 'status' => 'accepted', 'provider_message_id' => 'provider-message-'.$messageIds[0], 'attempts' => 1]);
        $this->assertDatabaseHas('email_messages', ['id' => $messageIds[1], 'status' => 'queued', 'attempts' => 0]);
        Queue::assertPushed(SendMarketingEmailBatchJob::class);

        DB::table('email_campaigns')->where('id', $campaignId)->update(['paused_at' => now()]);
        $job->handle(app(MarketingEmailProviderManager::class), app(RegionalEmailBrandingService::class));
        Http::assertSentCount(1);
        $this->assertDatabaseHas('email_messages', ['id' => $messageIds[1], 'status' => 'queued']);

        DB::table('email_campaigns')->where('id', $campaignId)->update(['paused_at' => null]);
        $job->handle(app(MarketingEmailProviderManager::class), app(RegionalEmailBrandingService::class));
        Http::assertSentCount(2);
        $this->assertDatabaseHas('email_messages', ['id' => $messageIds[1], 'status' => 'accepted', 'provider_message_id' => 'provider-message-'.$messageIds[1], 'attempts' => 1]);
        $this->assertDatabaseHas('email_campaigns', ['id' => $campaignId, 'status' => 'completed']);
        $this->assertSame(1, (int) DB::table('email_messages')->where('id', $messageIds[0])->value('attempts'));
    }

    public function test_newsletter_uses_frozen_consent_audience_and_marketing_provider_queue(): void
    {
        config([
            'marketing.email.provider' => 'generic_http',
            'marketing.email.api_base_url' => 'https://provider.example.test',
            'marketing.email.api_key' => 'test-provider-key',
            'marketing.email.sending_enabled' => true,
            'marketing.email.daily_limit' => 10,
        ]);
        Queue::fake();
        Http::fake(function ($request) {
            $messages = collect($request->data()['messages'] ?? [])->map(fn ($message) => [
                'client_reference' => $message['client_reference'],
                'id' => 'newsletter-provider-'.$message['client_reference'],
            ])->all();

            return Http::response(['status' => 'accepted', 'batch_id' => 'newsletter-batch', 'messages' => $messages], 202);
        });

        $marketplaceId = $this->marketplace('INDIA');
        DB::table('email_sender_profiles')->insert([
            'marketplace_id' => $marketplaceId, 'name' => 'India Newsletter Sender', 'purpose' => 'marketing',
            'from_name' => 'NeoGiga India', 'from_email' => 'news@news.neogiga.in', 'reply_to' => 'support@neogiga.in',
            'domain' => 'news.neogiga.in', 'base_url' => 'https://neogiga.in', 'default_currency' => 'INR',
            'default_language' => 'en', 'is_verified' => true, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('customer_profiles')->insert([
            ['marketplace_id' => $marketplaceId, 'email' => 'newsletter-opted@example.test', 'status' => 'active', 'marketing_opt_in' => true, 'marketing_status' => 'opted_in', 'created_at' => now(), 'updated_at' => now()],
            ['marketplace_id' => $marketplaceId, 'email' => 'newsletter-unknown@example.test', 'status' => 'active', 'marketing_opt_in' => false, 'marketing_status' => 'unknown', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('newsletter_subscribers')->insert([
            ['email' => 'newsletter-opted@example.test', 'name' => 'Opted In', 'status' => 'subscribed', 'consent_status' => 'opted_in', 'created_at' => now(), 'updated_at' => now()],
            ['email' => 'newsletter-unknown@example.test', 'name' => 'Unknown', 'status' => 'subscribed', 'consent_status' => 'unknown', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $templateId = DB::table('newsletter_templates')->insertGetId([
            'name' => 'Governed newsletter', 'slug' => 'governed-newsletter', 'subject' => 'NeoGiga engineering news',
            'html_body' => '<p>NeoGiga engineering news</p><a href="{{unsubscribe_url}}">Unsubscribe</a><a href="{{preferences_url}}">Preferences</a>',
            'text_body' => 'NeoGiga engineering news {{unsubscribe_url}} {{preferences_url}}', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $campaignId = DB::table('newsletter_campaigns')->insertGetId([
            'newsletter_template_id' => $templateId, 'marketplace_id' => $marketplaceId, 'name' => 'India engineering newsletter',
            'subject' => 'NeoGiga engineering news', 'status' => 'approved', 'requires_approval' => true, 'approved_at' => now(),
            'production_send_enabled' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $campaigns = app(CampaignExecutionService::class);
        $first = $campaigns->sendNewsletterCampaign($campaignId);
        $second = $campaigns->sendNewsletterCampaign($campaignId);
        $this->assertSame(1, $first['queued']);
        $this->assertSame(1, $first['skipped']);
        $this->assertTrue($second['snapshot_reused']);
        $this->assertSame(1, $second['already_prepared']);
        $this->assertDatabaseHas('newsletter_campaign_recipients', ['newsletter_campaign_id' => $campaignId, 'email' => 'newsletter-unknown@example.test', 'eligibility_status' => 'excluded']);
        $this->assertSame(1, DB::table('newsletter_audience_snapshots')->where('newsletter_campaign_id', $campaignId)->count());

        (new SendNewsletterEmailBatchJob($campaignId))->handle(app(MarketingEmailProviderManager::class), app(RegionalEmailBrandingService::class));
        $this->assertDatabaseHas('email_messages', ['newsletter_campaign_id' => $campaignId, 'to_email' => 'newsletter-opted@example.test', 'status' => 'accepted']);
        $this->assertDatabaseHas('newsletter_campaigns', ['id' => $campaignId, 'status' => 'completed']);
        Http::assertSentCount(1);
    }

    public function test_registration_verification_and_password_reset_use_transactional_queue(): void
    {
        Queue::fake();
        $email = 'account-flow@example.test';
        $registration = $this->postJson('/api/v1/auth/register', [
            'name' => 'Account Flow',
            'email' => $email,
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertCreated();
        $userId = (int) $registration->json('data.user.id');

        $registrationMessages = DB::table('email_messages')->where('to_email', $email)->orderBy('id')->get();
        $this->assertCount(2, $registrationMessages);
        $this->assertSame(['email_verification', 'registration_received'], $registrationMessages->map(fn ($message) => json_decode($message->metadata, true)['event_type'])->sort()->values()->all());
        $this->assertTrue($registrationMessages->every(fn ($message) => $message->queue_name === 'transactional'));

        $verificationMessage = $registrationMessages->first(fn ($message) => json_decode($message->metadata, true)['event_type'] === 'email_verification');
        $verificationBody = Crypt::decryptString(json_decode($verificationMessage->metadata, true)['sensitive_body_encrypted']);
        preg_match('/href="([^"]+)"/', html_entity_decode($verificationBody), $verificationLink);
        $this->assertNotEmpty($verificationLink[1] ?? null);
        $this->get($verificationLink[1])->assertOk()->assertJsonPath('data.message', 'Email verified successfully.');
        $this->assertNotNull(DB::table('users')->where('id', $userId)->value('email_verified_at'));

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $email])->assertOk();
        $resetMessage = DB::table('email_messages')->where('to_email', $email)->get()->first(fn ($message) => (json_decode($message->metadata, true)['event_type'] ?? null) === 'password_reset');
        $this->assertNotNull($resetMessage);
        $resetBody = Crypt::decryptString(json_decode($resetMessage->metadata, true)['sensitive_body_encrypted']);
        preg_match('/href="([^"]+)"/', html_entity_decode($resetBody), $resetLink);
        $resetToken = basename((string) parse_url($resetLink[1] ?? '', PHP_URL_PATH));
        $this->assertNotSame('', $resetToken);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => $email,
            'password' => 'NewStrongPassword123!',
            'password_confirmation' => 'NewStrongPassword123!',
        ])->assertOk();
        $this->assertTrue(DB::table('email_messages')->where('to_email', $email)->get()->contains(fn ($message) => (json_decode($message->metadata, true)['event_type'] ?? null) === 'password_changed'));
    }

    private function contactRow(string $name): array
    {
        return ['full_name' => $name, 'original_full_name' => $name, 'normalized_name' => mb_strtoupper($name), 'status' => 'active', 'transactional_eligible' => true, 'marketing_status' => 'unknown', 'source_name' => 'test', 'source_file' => 'test', 'confidence_level' => 'test', 'created_at' => now(), 'updated_at' => now()];
    }

    private function emailRow(int $contactId, string $email): array
    {
        return ['customer_contact_id' => $contactId, 'email' => $email, 'normalized_email' => $email, 'domain' => Str::after($email, '@'), 'is_primary' => true, 'is_valid' => true, 'is_verified' => false, 'status' => 'active', 'source_name' => 'test', 'source_file' => 'test', 'confidence_level' => 'test', 'created_at' => now(), 'updated_at' => now()];
    }

    private function marketplace(string $code): int
    {
        $countryId = DB::table('countries')->insertGetId(['name' => 'India', 'iso_code_2' => 'IN', 'iso_code_3' => 'IND', 'currency_code' => 'INR', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $currencyId = DB::table('currencies')->insertGetId(['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'decimal_places' => 2, 'exchange_rate' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('marketplaces')->insertGetId(['name' => 'NeoGiga India', 'code' => $code, 'country_id' => $countryId, 'currency_id' => $currencyId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }
}
