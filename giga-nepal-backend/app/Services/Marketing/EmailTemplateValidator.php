<?php

namespace App\Services\Marketing;

class EmailTemplateValidator
{
    public function validate(object|array $template, bool $marketing = true): array
    {
        $template = (array) $template;
        $subject = trim((string) ($template['subject'] ?? ''));
        $html = trim((string) ($template['html_body'] ?? ''));
        $text = trim((string) ($template['text_body'] ?? ''));
        $errors = [];
        $warnings = [];
        if ($subject === '') {
            $errors[] = 'subject_missing';
        }
        if ($html === '' && $text === '') {
            $errors[] = 'content_empty';
        }
        if ($marketing && ! str_contains($html.$text, '{{unsubscribe_url}}') && ! str_contains($html.$text, '/email/unsubscribe/')) {
            $errors[] = 'unsubscribe_link_missing';
        }
        if ($marketing && ! str_contains($html.$text, '{{preferences_url}}') && ! str_contains($html.$text, '/email/preferences/')) {
            $errors[] = 'preferences_link_missing';
        }
        if ($marketing && ! preg_match('/NeoGiga|Giga Nepal/i', $html.$text)) {
            $errors[] = 'company_identity_footer_missing';
        }
        if ($text === '') {
            $warnings[] = 'plain_text_missing';
        }
        if (preg_match('/[A-Z]{12,}/', $subject)) {
            $warnings[] = 'excessive_capitalization';
        }
        if (strlen($html) > 102400) {
            $warnings[] = 'oversized_email';
        }
        if (preg_match('/https?:\/\/(bit\.ly|tinyurl\.com|t\.co)\b/i', $html.$text)) {
            $warnings[] = 'shortened_link';
        }
        preg_match_all('/{{\s*([a-zA-Z0-9_]+)\s*}}/', $subject.$html.$text, $matches);
        $unknown = array_values(array_diff(array_unique($matches[1] ?? []), EmailTemplateService::VARIABLES));
        if ($unknown !== []) {
            $errors[] = 'unknown_variables:'.implode(',', $unknown);
        }

        return ['valid' => $errors === [], 'errors' => $errors, 'warnings' => $warnings, 'variables' => array_values(array_unique($matches[1] ?? []))];
    }

    public function unresolved(string $rendered): array
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_]+)\s*}}/', $rendered, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
