<?php

namespace App\Services\Marketing;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class EmailProviderConfigurationService
{
    public const CHANNELS = ['marketing', 'transactional'];

    public const MARKETING_TRANSPORTS = ['sandbox', 'smtp', 'generic_http'];

    public const TRANSACTIONAL_TRANSPORTS = ['log', 'smtp'];

    public function applyAll(): void
    {
        foreach (self::CHANNELS as $channel) {
            $this->apply($channel);
        }
    }

    public function apply(string $channel): ?array
    {
        $this->validateChannel($channel);
        $row = $this->row($channel);
        if (! $row) {
            return null;
        }

        $settings = $this->decode((string) ($row->settings ?? ''));
        $secrets = $this->decrypt((string) ($row->encrypted_settings ?? ''));
        $webhookSecret = $this->decryptString((string) ($row->webhook_secret_encrypted ?? ''));
        $transport = (string) ($settings['transport'] ?? ($channel === 'marketing' ? 'sandbox' : 'log'));
        $enabled = (bool) $row->is_enabled;
        $testMode = (bool) $row->test_mode;

        if ($transport === 'smtp') {
            $this->configureSmtpMailer($channel, $settings, $secrets);
        }

        if ($channel === 'marketing') {
            config([
                'marketing.email.provider' => $transport,
                'marketing.email.api_base_url' => $row->api_base_url ?: ($settings['api_base_url'] ?? null),
                'marketing.email.api_key' => $secrets['api_key'] ?? null,
                'marketing.email.account_id' => $row->account_id ?: ($settings['account_id'] ?? null),
                'marketing.email.webhook_secret' => $webhookSecret,
                'marketing.email.timeout' => (int) ($settings['timeout'] ?? 30),
                'marketing.email.test_mode' => $testMode,
                'marketing.email.sending_enabled' => $enabled && ! $testMode,
                'marketing.email.test_recipients' => $this->recipients($settings['test_recipients'] ?? []),
                'marketing.email.rate_limit_per_minute' => (int) $row->rate_limit_per_minute,
                'marketing.email.daily_limit' => (int) $row->daily_limit,
                'marketing.email.sender_profile_id' => $row->sender_profile_id,
            ]);
        } else {
            config([
                'marketing.transactional.mailer' => $transport === 'smtp' ? $this->mailerName($channel) : 'log',
                'marketing.transactional.enabled' => $enabled && ! $testMode,
                'marketing.transactional.test_mode' => $testMode,
                'marketing.transactional.test_recipient' => $settings['test_recipient'] ?? null,
                'marketing.transactional.timeout' => (int) ($settings['timeout'] ?? 30),
                'marketing.transactional.rate_limit_per_minute' => (int) $row->rate_limit_per_minute,
                'marketing.transactional.sender_profile_id' => $row->sender_profile_id,
            ]);
        }

        return $this->summary($channel);
    }

    public function save(string $channel, array $data): array
    {
        $this->validateChannel($channel);
        if (! $this->storageReady()) {
            throw new InvalidArgumentException('Email provider configuration storage is not installed.');
        }

        $existing = $this->row($channel);
        $existingSettings = $existing ? $this->decode((string) ($existing->settings ?? '')) : [];
        $secrets = $existing ? $this->decrypt((string) ($existing->encrypted_settings ?? '')) : [];
        if (! empty($data['clear_credentials'])) {
            $secrets = [];
        }
        foreach (['smtp_username', 'smtp_password', 'api_key'] as $key) {
            if (isset($data[$key]) && trim((string) $data[$key]) !== '') {
                $secrets[$key] = (string) $data[$key];
            }
        }

        $settings = array_merge($existingSettings, array_filter([
            'transport' => $data['transport'] ?? null,
            'smtp_host' => $data['smtp_host'] ?? null,
            'smtp_port' => isset($data['smtp_port']) ? (int) $data['smtp_port'] : null,
            'smtp_encryption' => $data['smtp_encryption'] ?? null,
            'smtp_local_domain' => $data['smtp_local_domain'] ?? null,
            'api_base_url' => $data['api_base_url'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'timeout' => isset($data['timeout']) ? (int) $data['timeout'] : null,
            'test_recipients' => $channel === 'marketing' ? $this->recipients($data['test_recipients'] ?? []) : null,
            'test_recipient' => $channel === 'transactional' ? mb_strtolower(trim((string) ($data['test_recipient'] ?? ''))) : null,
        ], fn ($value) => $value !== null));

        $transport = (string) ($settings['transport'] ?? '');
        $allowed = $channel === 'marketing' ? self::MARKETING_TRANSPORTS : self::TRANSACTIONAL_TRANSPORTS;
        if (! in_array($transport, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported email transport for this channel.');
        }
        if ($transport === 'smtp' && trim((string) ($settings['smtp_host'] ?? '')) === '') {
            throw new InvalidArgumentException('SMTP host is required.');
        }
        if ($transport === 'generic_http') {
            $this->assertSafeApiUrl((string) ($settings['api_base_url'] ?? ''));
            if (empty($secrets['api_key'])) {
                throw new InvalidArgumentException('An API key is required for the API transport.');
            }
        }

        $now = now();
        $values = [
            'channel' => $channel,
            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
            'test_mode' => (bool) ($data['test_mode'] ?? true),
            'settings' => json_encode($settings, JSON_UNESCAPED_SLASHES),
            'api_base_url' => $settings['api_base_url'] ?? null,
            'account_id' => $settings['account_id'] ?? null,
            'sender_profile_id' => $data['sender_profile_id'] ?? null,
            'sending_domain' => $data['sending_domain'] ?? null,
            'reply_to' => $data['reply_to'] ?? null,
            'rate_limit_per_minute' => (int) ($data['rate_limit_per_minute'] ?? 60),
            'daily_limit' => (int) ($data['daily_limit'] ?? 5000),
            'encrypted_settings' => $secrets === [] ? null : Crypt::encryptString(json_encode($secrets, JSON_UNESCAPED_SLASHES)),
            'updated_at' => $now,
        ];
        if (isset($data['webhook_secret']) && trim((string) $data['webhook_secret']) !== '') {
            $values['webhook_secret_encrypted'] = Crypt::encryptString((string) $data['webhook_secret']);
        } elseif (! empty($data['clear_credentials'])) {
            $values['webhook_secret_encrypted'] = null;
        }

        $key = $this->key($channel);
        if ($existing) {
            DB::table('email_provider_configs')->where('id', $existing->id)->update($values);
        } else {
            DB::table('email_provider_configs')->insert($values + ['provider' => $key, 'created_at' => $now]);
        }

        $this->apply($channel);

        return $this->summary($channel);
    }

    public function summary(string $channel): array
    {
        $this->validateChannel($channel);
        $row = $this->row($channel);
        if (! $row) {
            return [
                'channel' => $channel,
                'source' => 'environment',
                'transport' => $channel === 'marketing' ? (string) config('marketing.email.provider', 'sandbox') : (string) config('marketing.transactional.mailer', 'log'),
                'is_enabled' => $channel === 'marketing' ? (bool) config('marketing.email.sending_enabled', false) : (bool) config('marketing.transactional.enabled', false),
                'test_mode' => $channel === 'marketing' ? (bool) config('marketing.email.test_mode', true) : (bool) config('marketing.transactional.test_mode', true),
                'smtp_host' => null,
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'smtp_local_domain' => null,
                'api_base_url' => null,
                'account_id' => null,
                'sender_profile_id' => null,
                'sending_domain' => null,
                'reply_to' => null,
                'rate_limit_per_minute' => $channel === 'marketing' ? 60 : 120,
                'daily_limit' => 5000,
                'timeout' => 30,
                'test_recipients' => [],
                'test_recipient' => null,
                'smtp_username_configured' => false,
                'smtp_password_configured' => false,
                'api_key_configured' => false,
                'webhook_secret_configured' => false,
                'last_tested_at' => null,
                'last_test_status' => null,
            ];
        }

        $settings = $this->decode((string) ($row->settings ?? ''));
        $secrets = $this->decrypt((string) ($row->encrypted_settings ?? ''));

        return [
            'channel' => $channel,
            'source' => 'admin',
            'transport' => $settings['transport'] ?? ($channel === 'marketing' ? 'sandbox' : 'log'),
            'is_enabled' => (bool) $row->is_enabled,
            'test_mode' => (bool) $row->test_mode,
            'smtp_host' => $settings['smtp_host'] ?? null,
            'smtp_port' => (int) ($settings['smtp_port'] ?? 587),
            'smtp_encryption' => $settings['smtp_encryption'] ?? 'tls',
            'smtp_local_domain' => $settings['smtp_local_domain'] ?? null,
            'api_base_url' => $row->api_base_url ?: ($settings['api_base_url'] ?? null),
            'account_id' => $row->account_id ?: ($settings['account_id'] ?? null),
            'sender_profile_id' => $row->sender_profile_id,
            'sending_domain' => $row->sending_domain,
            'reply_to' => $row->reply_to,
            'rate_limit_per_minute' => (int) $row->rate_limit_per_minute,
            'daily_limit' => (int) $row->daily_limit,
            'timeout' => (int) ($settings['timeout'] ?? 30),
            'test_recipients' => $this->recipients($settings['test_recipients'] ?? []),
            'test_recipient' => $settings['test_recipient'] ?? null,
            'smtp_username_configured' => ! empty($secrets['smtp_username']),
            'smtp_password_configured' => ! empty($secrets['smtp_password']),
            'api_key_configured' => ! empty($secrets['api_key']),
            'webhook_secret_configured' => ! empty($row->webhook_secret_encrypted),
            'last_tested_at' => $row->last_tested_at,
            'last_test_status' => $row->last_test_status,
        ];
    }

    public function markTested(string $channel, string $status): void
    {
        $row = $this->row($channel);
        if ($row) {
            DB::table('email_provider_configs')->where('id', $row->id)->update([
                'last_tested_at' => now(),
                'last_test_status' => mb_substr($status, 0, 40),
                'updated_at' => now(),
            ]);
        }
    }

    private function configureSmtpMailer(string $channel, array $settings, array $secrets): void
    {
        $encryption = (string) ($settings['smtp_encryption'] ?? 'tls');
        config([
            'mail.mailers.'.$this->mailerName($channel) => [
                'transport' => 'smtp',
                'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
                'host' => (string) ($settings['smtp_host'] ?? '127.0.0.1'),
                'port' => (int) ($settings['smtp_port'] ?? ($encryption === 'ssl' ? 465 : 587)),
                'username' => $secrets['smtp_username'] ?? null,
                'password' => $secrets['smtp_password'] ?? null,
                'timeout' => (int) ($settings['timeout'] ?? 30),
                'auto_tls' => $encryption !== 'none',
                'local_domain' => $settings['smtp_local_domain'] ?? parse_url((string) config('app.url'), PHP_URL_HOST),
            ],
        ]);
        Mail::forgetMailers();
    }

    private function row(string $channel): ?object
    {
        if (! $this->storageReady()) {
            return null;
        }

        return DB::table('email_provider_configs')->where('provider', $this->key($channel))->where('channel', $channel)->first();
    }

    private function storageReady(): bool
    {
        if (! Schema::hasTable('email_provider_configs')) {
            return false;
        }

        $required = [
            'provider', 'channel', 'settings', 'encrypted_settings', 'webhook_secret_encrypted',
            'api_base_url', 'account_id', 'sender_profile_id', 'sending_domain', 'reply_to',
            'rate_limit_per_minute', 'daily_limit', 'is_enabled', 'test_mode',
            'last_tested_at', 'last_test_status',
        ];

        return array_diff($required, Schema::getColumnListing('email_provider_configs')) === [];
    }

    private function key(string $channel): string
    {
        return 'admin_'.$channel;
    }

    private function mailerName(string $channel): string
    {
        return 'neogiga_'.$channel.'_smtp';
    }

    private function decode(string $value): array
    {
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function decrypt(string $value): array
    {
        if ($value === '') {
            return [];
        }
        try {
            return $this->decode(Crypt::decryptString($value));
        } catch (DecryptException) {
            return [];
        }
    }

    private function decryptString(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    private function recipients(array|string $value): array
    {
        $items = is_array($value) ? $value : preg_split('/[,\r\n]+/', $value);

        return array_values(array_unique(array_filter(array_map(
            fn ($email) => filter_var(mb_strtolower(trim((string) $email)), FILTER_VALIDATE_EMAIL) ? mb_strtolower(trim((string) $email)) : null,
            $items ?: [],
        ))));
    }

    private function assertSafeApiUrl(string $url): void
    {
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['https'], true)) {
            throw new InvalidArgumentException('The provider API base URL must be a valid HTTPS URL.');
        }
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === 'localhost' || str_ends_with($host, '.local')) {
            throw new InvalidArgumentException('Local provider API hosts are not accepted.');
        }
    }

    private function validateChannel(string $channel): void
    {
        if (! in_array($channel, self::CHANNELS, true)) {
            throw new InvalidArgumentException('Unsupported email channel.');
        }
    }
}
