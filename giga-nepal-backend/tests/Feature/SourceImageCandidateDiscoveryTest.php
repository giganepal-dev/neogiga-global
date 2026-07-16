<?php

namespace Tests\Feature;

use App\Services\Product\SourceImageCandidateDiscovery;
use Tests\TestCase;

class SourceImageCandidateDiscoveryTest extends TestCase
{
    public function test_extracts_mpn_matched_image_from_official_json_ld(): void
    {
        $html = <<<'HTML'
<html><head><script type="application/ld+json">
{"@type":"Product","mpn":"INA950-SEP","image":"/content/dam/ticom/images/products/package/p/pw0008a.png:singlesmall"}
</script></head><body></body></html>
HTML;

        $candidates = app(SourceImageCandidateDiscovery::class)->extractCandidates(
            $html,
            'https://www.ti.com/product/INA950-SEP',
            'INA950-SEP',
        );

        $this->assertCount(1, $candidates);
        $this->assertSame('https://www.ti.com/content/dam/ticom/images/products/package/p/pw0008a.png:singlesmall', $candidates[0]['url']);
        $this->assertTrue($candidates[0]['matched_mpn']);
        $this->assertSame(0.95, $candidates[0]['confidence']);
    }

    public function test_ignores_placeholder_and_unrelated_non_image_values(): void
    {
        $html = <<<'HTML'
<html><head><script type="application/ld+json">
{"@type":"Product","mpn":"INA950-SEP","image":"/content/dam/ticom/images/icons/svg-icons/no-image-available-icon.svg","url":"/product/INA950-SEP"}
</script></head><body></body></html>
HTML;

        $candidates = app(SourceImageCandidateDiscovery::class)->extractCandidates(
            $html,
            'https://www.ti.com/product/INA950-SEP',
            'INA950-SEP',
        );

        $this->assertSame([], $candidates);
    }
}
