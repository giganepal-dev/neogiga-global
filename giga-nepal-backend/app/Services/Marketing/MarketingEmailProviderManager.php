<?php

namespace App\Services\Marketing;

use App\Services\Marketing\Contracts\MarketingEmailProviderInterface;
use App\Services\Marketing\Providers\GenericHttpMarketingEmailProvider;
use App\Services\Marketing\Providers\SandboxMarketingEmailProvider;
use App\Services\Marketing\Providers\SmtpMarketingEmailProvider;
use RuntimeException;

class MarketingEmailProviderManager
{
    public function __construct(private EmailProviderConfigurationService $configuration) {}

    public function provider(?string $name = null): MarketingEmailProviderInterface
    {
        $this->configuration->apply('marketing');
        $name ??= (string) config('marketing.email.provider', 'sandbox');

        return match ($name) {
            'sandbox', 'log' => app(SandboxMarketingEmailProvider::class),
            'smtp' => app(SmtpMarketingEmailProvider::class),
            'generic_http' => app(GenericHttpMarketingEmailProvider::class),
            default => throw new RuntimeException("Marketing email provider [{$name}] is not installed."),
        };
    }

    public function testConnection(): array
    {
        return $this->provider()->testConnection();
    }
}
