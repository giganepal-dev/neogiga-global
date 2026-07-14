<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Role;
use App\Models\User;
use App\Services\Marketing\EmailProviderConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerCommunicationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'customer-communication-admin-token';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.admin_api_token' => self::TOKEN,
            'services.admin_api_token_hash' => null,
            'services.admin_api_token_permissions' => [],
        ]);
    }

    public function test_web_import_and_export_require_explicit_permissions(): void
    {
        $admin = $this->admin([]);

        $this->actingAs($admin)->get('/admin/marketing/customer-imports')->assertForbidden();
        $this->actingAs($admin)->get('/admin/marketing/customers/export')->assertForbidden();
    }

    public function test_admin_api_campaign_routes_fail_closed_without_campaign_permission(): void
    {
        $this->getJson('/api/v1/admin/email/campaigns', $this->auth())
            ->assertForbidden()
            ->assertJsonPath('required_permission', 'campaigns.view');
    }

    public function test_renamed_non_spreadsheet_upload_is_rejected(): void
    {
        $admin = $this->admin(['customers.import']);
        $file = UploadedFile::fake()->createWithContent('not-a-spreadsheet.xlsx', 'this is plain text, not an xlsx archive');

        $this->actingAs($admin)
            ->post('/admin/marketing/customer-imports/preview', [
                'file' => $file,
                'profile' => 'Customer Invoice Details',
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_settings_api_never_returns_secret_like_records(): void
    {
        DB::table('marketing_settings')->insert([
            ['key' => 'campaign_daily_limit', 'value' => json_encode(250), 'group' => 'marketing', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'provider_api_key', 'value' => json_encode('super-secret'), 'group' => 'marketing', 'created_at' => now(), 'updated_at' => now()],
        ]);
        config(['services.admin_api_token_permissions' => ['email.providers.manage']]);

        $response = $this->getJson('/api/v1/admin/settings/marketing', $this->auth())
            ->assertOk()
            ->assertJsonFragment(['key' => 'campaign_daily_limit']);

        $this->assertStringNotContainsString('provider_api_key', $response->getContent());
        $this->assertStringNotContainsString('super-secret', $response->getContent());
    }

    public function test_customer_api_marketplace_filter_does_not_mix_regional_profiles(): void
    {
        [$india, $nepal] = $this->marketplaces();
        DB::table('customer_profiles')->insert([
            ['marketplace_id' => $india->id, 'email' => 'india@example.test', 'status' => 'active', 'marketing_status' => 'unknown', 'created_at' => now(), 'updated_at' => now()],
            ['marketplace_id' => $nepal->id, 'email' => 'nepal@example.test', 'status' => 'active', 'marketing_status' => 'unknown', 'created_at' => now(), 'updated_at' => now()],
        ]);
        config(['services.admin_api_token_permissions' => ['customers.view']]);

        $this->getJson('/api/v1/admin/customers?marketplace='.$india->id, $this->auth())
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.email', 'india@example.test');
    }

    public function test_admin_can_save_smtp_configuration_with_encrypted_credentials(): void
    {
        $admin = $this->admin(['email.providers.manage']);

        $this->actingAs($admin)->post('/admin/marketing/settings/email-provider', [
            'channel' => 'marketing',
            'transport' => 'smtp',
            'is_enabled' => '1',
            'test_mode' => '1',
            'smtp_host' => 'smtp.example.test',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => 'smtp-user',
            'smtp_password' => 'smtp-super-secret',
            'rate_limit_per_minute' => 45,
            'daily_limit' => 1200,
            'timeout' => 25,
            'test_recipients' => "safe-one@example.test\nsafe-two@example.test",
        ])->assertRedirect()->assertSessionHas('status');

        $row = DB::table('email_provider_configs')->where('provider', 'admin_marketing')->first();
        $this->assertNotNull($row);
        $this->assertStringNotContainsString('smtp-super-secret', (string) $row->encrypted_settings);
        $secrets = json_decode(Crypt::decryptString($row->encrypted_settings), true);
        $this->assertSame('smtp-user', $secrets['smtp_username']);
        $this->assertSame('smtp-super-secret', $secrets['smtp_password']);

        $summary = app(EmailProviderConfigurationService::class)->summary('marketing');
        $this->assertSame('smtp', $summary['transport']);
        $this->assertTrue($summary['smtp_password_configured']);
        $this->assertTrue((bool) config('marketing.email.test_mode'));
        $this->assertFalse((bool) config('marketing.email.sending_enabled'));
        $this->assertSame('smtp.example.test', config('mail.mailers.neogiga_marketing_smtp.host'));
        $this->assertSame('smtp-user', config('mail.mailers.neogiga_marketing_smtp.username'));
        $this->assertSame('smtp-super-secret', config('mail.mailers.neogiga_marketing_smtp.password'));

        $this->actingAs($admin)->get('/admin/marketing/settings')
            ->assertOk()
            ->assertSee('smtp.example.test')
            ->assertDontSee('smtp-super-secret')
            ->assertDontSee('smtp-user');
    }

    public function test_admin_can_compose_content_and_create_campaign_from_the_panel(): void
    {
        $admin = $this->admin(['campaigns.view', 'campaigns.create', 'email.templates.manage']);

        $this->actingAs($admin)->post('/admin/marketing/email/templates', [
            'name' => 'Engineering launch content',
            'type' => 'campaign',
            'subject' => 'NeoGiga engineering launch',
            'html_body' => '<p>Hello {{customer_name}}</p><p>NeoGiga engineering marketplace</p><a href="{{unsubscribe_url}}">Unsubscribe</a><a href="{{preferences_url}}">Preferences</a>',
            'text_body' => 'Hello {{customer_name}} from NeoGiga {{unsubscribe_url}} {{preferences_url}}',
        ])->assertRedirect()->assertSessionHas('status');
        $templateId = (int) DB::table('email_templates')->where('name', 'Engineering launch content')->value('id');

        $this->actingAs($admin)->post('/admin/marketing/email/campaigns', [
            'name' => 'Engineering customer launch',
            'type' => 'marketing',
            'email_template_id' => $templateId,
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('email_campaigns', [
            'name' => 'Engineering customer launch',
            'email_template_id' => $templateId,
            'status' => 'draft',
            'production_send_enabled' => false,
        ]);
        $this->actingAs($admin)->get('/admin/marketing/email')
            ->assertOk()
            ->assertSee('Engineering launch content')
            ->assertSee('Engineering customer launch')
            ->assertSee('Import customers from Excel');
    }

    private function admin(array $permissions): User
    {
        $role = Role::create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'permissions' => $permissions,
            'is_active' => true,
        ]);

        return User::create([
            'name' => 'Communication Admin',
            'email' => 'communication-admin@example.test',
            'password' => 'secret',
            'role_id' => $role->id,
        ]);
    }

    /** @return array{Marketplace, Marketplace} */
    private function marketplaces(): array
    {
        $currency = Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$', 'decimal_places' => 2, 'exchange_rate' => 1, 'is_active' => true]);
        $indiaCountry = Country::create(['name' => 'India', 'iso_code_2' => 'IN', 'iso_code_3' => 'IND', 'is_active' => true]);
        $nepalCountry = Country::create(['name' => 'Nepal', 'iso_code_2' => 'NP', 'iso_code_3' => 'NPL', 'is_active' => true]);

        return [
            Marketplace::create(['name' => 'NeoGiga India', 'code' => 'INDIA', 'country_id' => $indiaCountry->id, 'currency_id' => $currency->id, 'is_active' => true]),
            Marketplace::create(['name' => 'Giga Nepal', 'code' => 'NEPAL', 'country_id' => $nepalCountry->id, 'currency_id' => $currency->id, 'is_active' => true]),
        ];
    }

    private function auth(): array
    {
        return ['X-Admin-Token' => self::TOKEN];
    }
}
