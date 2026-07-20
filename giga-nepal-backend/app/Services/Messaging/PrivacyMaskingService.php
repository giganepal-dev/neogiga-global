<?php

namespace App\Services\Messaging;

/**
 * Detects and masks personally identifiable information in message text.
 *
 * Mask levels:
 *  - full:     name, email, phone, address, URL, WhatsApp, social handles, payment info
 *  - partial:  email + phone only (names/companies visible)
 *  - none:     no masking (admin/support use only)
 *
 * Each masker uses a regex replace. The service is stateless — call mask()
 * on every message body before persisting the masked version.
 */
class PrivacyMaskingService
{
    /**
     * Patterns keyed by what they detect. Order matters — longer patterns
     * (addresses) run before shorter ones (phone numbers inside addresses).
     */
    private const PATTERNS = [
        // Email addresses
        'email' => '~[\w\.\-\+]+@[\w\.\-]+\.[a-zA-Z]{2,}~',

        // Phone numbers (international + country variants)
        'phone' => '~(?:\+?\d{1,3}[\s\-\.]?)?\(?\d{2,4}\)?[\s\-\.]?\d{2,4}[\s\-\.]?\d{2,6}(?:\s*(?:ext|x|ext\.)\s*\d+)?~',

        // URLs
        'url' => '~https?://[^\s\)\]\}\>]+~',

        // WhatsApp numbers (explicitly mentioned)
        'whatsapp' => '~(?:whatsapp|wa|wap)[\s:\.]*(?:\+?\d[\d\s\-\.\(\)]{6,18}\d)~i',

        // Social handles (@mentions on platforms)
        'social' => '~(?:@[a-zA-Z0-9_\.]{3,30}\s+(?:on\s+)?(?:twitter|instagram|facebook|telegram|discord|skype|signal|wechat|line|viber|snapchat|tiktok))~i',

        // Payment identifiers (UPI, bank account, etc.)
        'payment' => [
            '~(?:UPI|upi)[\s:\.]*[\w\.\-]+@[\w]+~i',
            '~(?:account|acct|a/c)[\s#:\.]*\d{6,20}~i',
            '~(?:IFSC|ifsc|SWIFT|swift|BIC|bic|routing)[\s:\.]*[A-Z0-9]{8,16}~i',
            '~(?:card|cc|credit\s*card)[\s#:\.]*\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}~i',
        ],

        // Physical addresses (heuristic: street patterns)
        'address' => [
            '~(?:address|addr|location)[\s:\.]*.+~i',
            '~\d{2,5}\s+[A-Za-z\s]+(?:street|st|road|rd|avenue|ave|lane|ln|drive|dr|boulevard|blvd|colony|nagar|sector|phase|block|plot|flat|apartment|apt|building|bldg|floor|fl)[\s,\.]+[A-Za-z\s,\.]+~i',
            '~\b\d{5,6}\b~',
        ],
    ];

    /**
     * Mask a message body according to the receiver's mask level.
     */
    public function mask(string $body, string $maskLevel = 'full'): string
    {
        if ($maskLevel === 'none') {
            return $body;
        }

        $masked = $body;

        // Always mask email + phone (even at partial)
        $masked = $this->applyMask($masked, 'email', '[email hidden]');
        $masked = $this->applyMask($masked, 'phone', '[phone hidden]');

        if ($maskLevel === 'full') {
            foreach (['url', 'whatsapp', 'social', 'payment', 'address'] as $category) {
                $patterns = self::PATTERNS[$category];
                $patterns = is_array($patterns) ? $patterns : [$patterns];
                foreach ($patterns as $pattern) {
                    $masked = $this->applyMask($masked, $category, "[{$category} hidden]", $pattern);
                }
            }
        }

        return $masked;
    }

    /**
     * Apply a single masking pattern.
     */
    private function applyMask(string $text, string $category, string $replacement, ?string $pattern = null): string
    {
        $pattern ??= self::PATTERNS[$category] ?? null;
        if (! $pattern) {
            return $text;
        }

        $patterns = is_array($pattern) ? $pattern : [$pattern];
        foreach ($patterns as $p) {
            $text = preg_replace($p, $replacement, $text);
        }

        return $text;
    }

    /**
     * Check whether a body contains any detectable PII.
     */
    public function containsPii(string $body): bool
    {
        return $this->mask($body, 'full') !== $body;
    }

    /**
     * Extract a summary of what was masked for audit logging.
     */
    public function maskedSummary(string $original, string $masked): array
    {
        if ($original === $masked) {
            return [];
        }

        $categories = ['email', 'phone', 'url', 'whatsapp', 'social', 'payment', 'address'];
        $found = [];

        foreach ($categories as $cat) {
            $tested = $this->applyMask($original, $cat, '');
            if ($tested !== $original) {
                $found[] = $cat;
            }
        }

        return $found;
    }
}
