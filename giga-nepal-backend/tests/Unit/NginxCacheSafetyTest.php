<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NginxCacheSafetyTest extends TestCase
{
    #[Test]
    public function storefront_cache_preserves_session_and_private_response_headers(): void
    {
        $config = file_get_contents(dirname(__DIR__, 2).'/deploy/nginx/neogiga.com.conf');

        $this->assertIsString($config);
        $this->assertStringContainsString(
            'fastcgi_no_cache $cookie_neogiga_session $http_authorization $upstream_http_set_cookie;',
            $config,
        );
        $this->assertStringContainsString('fastcgi_cache_valid 200 60m;', $config);
        $this->assertStringNotContainsString('fastcgi_cache_valid 200 301 302', $config);
        $this->assertStringNotContainsString('fastcgi_ignore_headers', $config);
        $this->assertStringNotContainsString('fastcgi_hide_header Set-Cookie', $config);
    }
}
