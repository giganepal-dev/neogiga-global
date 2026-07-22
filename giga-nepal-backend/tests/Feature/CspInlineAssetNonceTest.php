<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CspInlineAssetNonceTest extends TestCase
{
    public function test_every_blade_inline_style_and_script_declares_the_request_nonce(): void
    {
        $violations = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if ($file->getExtension() !== 'php' || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = File::get($file->getPathname());
            if (preg_match_all('/<(style|script)(?=[\\s>])(?![^>]*\\bnonce=)[^>]*>/i', $contents, $matches)) {
                $violations[] = $file->getRelativePathname().': '.implode(', ', $matches[0]);
            }
        }

        $this->assertSame([], $violations, "Inline CSP assets without a nonce:\n".implode("\n", $violations));
    }
}
