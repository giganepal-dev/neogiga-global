<?php

namespace Tests\Feature;

use App\Models\Marketplace\ProductBrand;
use App\Models\User;
use App\Services\Catalog\BrandIdentityResolver;
use App\Services\Catalog\BrandLogoDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BrandLogoGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_domain_logo_is_staged_and_can_be_approved(): void
    {
        Storage::fake('public');
        config(['brand_logos.official_domains.acme' => 'official.example']);
        $brand = $this->brand('Acme', 'https://official.example');
        $canvas = imagecreatetruecolor(320, 120);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 10, 80, 140));
        ob_start(); imagepng($canvas); $png = ob_get_clean(); imagedestroy($canvas);
        Http::fake([
            'https://official.example' => Http::response('<script type="application/ld+json">{"@type":"Organization","logo":"https://official.example/media/acme-logo.png"}</script>', 200, ['Content-Type' => 'text/html']),
            'https://official.example/media/acme-logo.png' => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);

        $logos = app(BrandLogoDiscoveryService::class);
        $plan = $logos->discoverOfficialLogo($brand);
        $this->assertSame('stage_for_approval', $plan['action']);
        $history = $logos->stageDiscoveredLogo($brand, $plan, null);
        $approved = $logos->approveStagedLogo($brand, $history, $this->user()->id);

        $this->assertTrue($approved->logo_verified);
        $this->assertSame('verified', $approved->logo_status);
        $this->assertSame('official.example', $approved->logo_source_domain);
        $this->assertNotNull($approved->verifiedLogoUrl());
        Storage::disk('public')->assertExists($approved->logo_metadata['original_path']);
        $this->get('/en/brand/'.$brand->slug)->assertOk()->assertSee($approved->verifiedLogoUrl(), false);
    }

    public function test_reseller_domain_logo_is_rejected(): void
    {
        config(['brand_logos.official_domains.acme' => 'official.example']);
        $brand = $this->brand('Acme', 'https://official.example');
        $result = app(BrandLogoDiscoveryService::class)->validateLogoMatch($brand, 'official.example', [
            'url' => 'https://reseller.example/assets/acme-logo.png',
            'alt' => 'Acme logo',
            'source' => 'logo',
        ]);

        $this->assertFalse($result['acceptable']);
    }

    public function test_malformed_svg_is_rejected(): void
    {
        Storage::fake('public');
        $brand = $this->brand('Acme');
        $file = UploadedFile::fake()->createWithContent('acme.svg', '<svg><script>alert(1)</script></svg>');

        $this->expectException(ValidationException::class);
        app(BrandLogoDiscoveryService::class)->stageManualUpload($brand, $file, []);
    }

    public function test_verified_logo_cannot_be_replaced_by_lower_confidence_candidate(): void
    {
        Storage::fake('public');
        $brand = $this->brand('Acme');
        $brand->update(['logo_verified' => true, 'logo_confidence' => 0.98, 'logo_status' => 'verified']);
        $file = UploadedFile::fake()->image('candidate.png', 320, 120);
        $history = app(BrandLogoDiscoveryService::class)->stageManualUpload($brand, $file, ['confidence' => 0.60]);

        $this->expectException(ValidationException::class);
        app(BrandLogoDiscoveryService::class)->approveStagedLogo($brand, $history, 1);
    }

    public function test_brand_aliases_reuse_existing_brand_and_missing_logo_falls_back_to_initial(): void
    {
        $brand = $this->brand('TE Connectivity');
        $resolved = app(BrandIdentityResolver::class)->resolveOrCreate('TE Connectivity AMP');
        $this->assertFalse($resolved['created']);
        $this->assertSame($brand->id, $resolved['brand']->id);

        $this->get('/en/brand/'.$brand->slug)
            ->assertOk()
            ->assertSee('aria-label="TE Connectivity"', false)
            ->assertDontSee('<img src=""', false);
    }

    public function test_logo_audit_is_read_only_and_writes_the_requested_reports(): void
    {
        Storage::fake('local');
        $brand = $this->brand('Audit Brand');
        $before = $brand->updated_at?->toIso8601String();

        Artisan::call('catalog:audit-brand-logos', ['--output' => 'private/brand-logo-audits/test']);

        Storage::disk('local')->assertExists('private/brand-logo-audits/test/BRAND_LOGO_AUDIT.md');
        Storage::disk('local')->assertExists('private/brand-logo-audits/test/BRAND_LOGO_MAPPING_PLAN.csv');
        $this->assertSame($before, $brand->fresh()->updated_at?->toIso8601String());
    }

    private function brand(string $name, ?string $website = null): ProductBrand
    {
        return ProductBrand::create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'website_url' => $website,
            'is_active' => true,
            'landing_page_enabled' => true,
            'hide_when_unavailable' => false,
        ]);
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Logo Reviewer',
            'email' => 'logo-reviewer-'.uniqid().'@example.com',
            'password' => 'reviewer-password',
        ]);
    }
}
