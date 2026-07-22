<?php

namespace Tests\Unit;

use App\Services\Messaging\PrivacyMaskingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PrivacyMaskingServiceTest extends TestCase
{
    private PrivacyMaskingService $masker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->masker = new PrivacyMaskingService();
    }

    #[Test]
    public function it_masks_email_addresses(): void
    {
        $input = 'Contact me at john.doe@example.com for details.';
        $masked = $this->masker->mask($input, 'full');

        $this->assertStringNotContainsString('john.doe@example.com', $masked);
        $this->assertStringContainsString('[email hidden]', $masked);
    }

    #[Test]
    public function it_masks_phone_numbers(): void
    {
        $input = 'Call me at +977-9841-123456 or 01-4123456.';
        $masked = $this->masker->mask($input, 'full');

        $this->assertStringNotContainsString('9841-123456', $masked);
        $this->assertStringNotContainsString('4123456', $masked);
        $this->assertStringContainsString('[phone hidden]', $masked);
    }

    #[Test]
    public function it_masks_urls(): void
    {
        $input = 'Visit https://example.com/page for more info.';
        $masked = $this->masker->mask($input, 'full');

        $this->assertStringNotContainsString('https://example.com/page', $masked);
        $this->assertStringContainsString('[url hidden]', $masked);
    }

    #[Test]
    public function it_masks_whatsapp_numbers(): void
    {
        $input = 'Reach me on WhatsApp: +977 9841123456.';
        $masked = $this->masker->mask($input, 'full');

        $this->assertStringNotContainsString('+977', $masked);
    }

    #[Test]
    public function it_masks_payment_identifiers(): void
    {
        $input = 'Pay via UPI: merchant@bank or account 1234567890.';
        $masked = $this->masker->mask($input, 'full');

        $this->assertStringNotContainsString('merchant@bank', $masked);
    }

    #[Test]
    public function it_masks_addresses(): void
    {
        $input = 'Ship to: 42 Main Street, Kathmandu 44600.';
        $masked = $this->masker->mask($input, 'full');

        $this->assertStringNotContainsString('44600', $masked);
    }

    #[Test]
    public function it_masks_social_handles(): void
    {
        $input = 'DM me @johndoe on Twitter for faster response.';
        $masked = $this->masker->mask($input, 'full');

        $this->assertStringNotContainsString('@johndoe on Twitter', $masked);
    }

    #[Test]
    public function partial_mask_level_only_masks_email_and_phone(): void
    {
        $input = 'Email: test@example.com, Phone: 9841123456, URL: https://example.com.';
        $masked = $this->masker->mask($input, 'partial');

        $this->assertStringNotContainsString('test@example.com', $masked);
        $this->assertStringNotContainsString('9841123456', $masked);
        $this->assertStringContainsString('https://example.com', $masked);  // URL preserved
    }

    #[Test]
    public function none_mask_level_preserves_everything(): void
    {
        $input = 'Email: test@example.com, Phone: 9841123456.';
        $masked = $this->masker->mask($input, 'none');

        $this->assertEquals($input, $masked);
    }

    #[Test]
    public function clean_text_is_unchanged(): void
    {
        $input = 'This is a normal message about product specifications.';
        $masked = $this->masker->mask($input, 'full');

        $this->assertEquals($input, $masked);
    }

    #[Test]
    public function it_detects_pii_in_text(): void
    {
        $this->assertTrue($this->masker->containsPii('Email me at test@example.com'));
        $this->assertFalse($this->masker->containsPii('This is a normal message.'));
    }

    #[Test]
    public function it_reports_what_was_masked(): void
    {
        $original = 'Email: test@example.com, Phone: 9841123456';
        $masked = $this->masker->mask($original, 'full');
        $summary = $this->masker->maskedSummary($original, $masked);

        $this->assertContains('email', $summary);
        $this->assertContains('phone', $summary);
    }
}
