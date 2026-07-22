<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CatalogUiSecurityHeadersTest extends TestCase
{
    public function test_csp_keeps_style_elements_nonce_protected_and_allows_legacy_style_attributes(): void
    {
        $response = app(SecurityHeaders::class)->handle(
            Request::create('/login', 'GET'),
            fn () => response('ok'),
        );

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/style-src-elem 'self' 'nonce-[A-Za-z0-9]+';/", $csp);
        $this->assertStringContainsString("style-src-attr 'unsafe-inline';", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
    }

    public function test_product_structured_data_blocks_do_not_contain_interaction_javascript(): void
    {
        $blade = File::get(resource_path('views/frontend/products/show.blade.php'));

        preg_match_all('/<script[^>]+application\/ld\+json[^>]*>(.*?)<\/script>/s', $blade, $matches);

        $this->assertCount(2, $matches[1]);
        foreach ($matches[1] as $structuredDataBlock) {
            $this->assertStringNotContainsString('document.querySelectorAll', $structuredDataBlock);
        }
    }
}
