<?php

namespace App\Services\CustomerImport;

use Carbon\CarbonImmutable;
use Throwable;

class CustomerImportNormalizer
{
    public function normalize(array $values): array
    {
        $company = $this->clean($values['company_name'] ?? null);
        $contact = $this->clean($values['contact_name'] ?? null);
        $email = mb_strtolower($this->clean($values['contact_email'] ?? null));
        $phone = $this->normalizePhone($values['contact_phone'] ?? null);
        $date = $this->normalizeDate($values['invoice_or_sales_order_date'] ?? null);

        return [
            'external_invoice_id' => $this->cleanIdentifier($values['external_invoice_id'] ?? null),
            'invoice_or_sales_order_date' => $date,
            'address_line_1' => $this->clean($values['address_line_1'] ?? null),
            'city' => $this->title($values['city'] ?? null),
            'account_country_name' => $this->clean($values['account_country_name'] ?? null),
            'company_name' => $company,
            'normalized_company_name' => $this->normalizedCompany($company),
            'postal_code' => mb_strtoupper($this->clean($values['postal_code'] ?? null)),
            'source_region_name' => $this->title($values['source_region_name'] ?? null),
            'contact_email' => $email,
            'contact_email_domain' => str_contains($email, '@') ? substr(strrchr($email, '@'), 1) : null,
            'contact_name' => $this->title($contact),
            'original_contact_name' => $contact,
            'normalized_contact_name' => $this->normalizedName($contact),
            'contact_name_parts' => $this->nameParts($contact),
            'contact_phone' => $phone,
            'source_country_text' => $this->clean($values['source_country_text'] ?? null),
        ];
    }

    public function validate(array $original, array $normalized, array $countryResolution): array
    {
        $errors = [];
        $warnings = [];
        foreach (['external_invoice_id', 'company_name', 'contact_name', 'contact_email'] as $field) {
            if (blank($normalized[$field] ?? null)) {
                $errors[] = ['field' => $field, 'code' => 'required', 'message' => "{$field} is required."];
            }
        }
        if (($normalized['contact_email'] ?? '') !== '' && ! filter_var($normalized['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['field' => 'contact_email', 'code' => 'invalid_email', 'message' => 'Email syntax is invalid.'];
        }
        $digits = preg_replace('/\D+/', '', (string) ($normalized['contact_phone'] ?? ''));
        if (($normalized['contact_phone'] ?? '') !== '' && (strlen($digits) < 7 || strlen($digits) > 18)) {
            $errors[] = ['field' => 'contact_phone', 'code' => 'invalid_phone', 'message' => 'Phone number length is invalid.'];
        }
        if (($original['invoice_or_sales_order_date'] ?? null) !== null && ($original['invoice_or_sales_order_date'] ?? '') !== '' && ! $normalized['invoice_or_sales_order_date']) {
            $errors[] = ['field' => 'invoice_or_sales_order_date', 'code' => 'invalid_date', 'message' => 'Invoice or sales-order date is invalid.'];
        }
        if (! ($countryResolution['resolved'] ?? null)) {
            $errors[] = ['field' => 'account_country_name', 'code' => 'unresolved_country', 'message' => 'Country could not be resolved to a canonical ISO record.'];
        }
        if ($countryResolution['conflict'] ?? false) {
            $warnings[] = ['field' => 'account_country_name', 'code' => 'country_conflict', 'message' => 'Account country and source country text resolve to different countries.'];
        }
        foreach ($original as $field => $value) {
            if (! is_string($value)) {
                continue;
            }
            if (mb_strlen($value) > 20000) {
                $errors[] = ['field' => $field, 'code' => 'field_too_long', 'message' => 'Field exceeds the safe import length.'];
            }
            if ($field !== 'contact_phone' && $this->hasFormulaPrefix($value)) {
                $warnings[] = ['field' => $field, 'code' => 'formula_injection_risk', 'message' => 'Value begins with a spreadsheet formula prefix and will be escaped on export.'];
            }
            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
                $errors[] = ['field' => $field, 'code' => 'unsupported_control_character', 'message' => 'Value contains unsupported control characters.'];
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    public function escapeForSpreadsheetExport(mixed $value): mixed
    {
        if (! is_string($value) || ! $this->hasFormulaPrefix($value)) {
            return $value;
        }

        return "'".$value;
    }

    private function hasFormulaPrefix(string $value): bool
    {
        return in_array(mb_substr(ltrim($value), 0, 1), config('customer_import.formula_prefixes', ['=', '+', '-', '@']), true);
    }

    private function clean(mixed $value): string
    {
        $value = is_scalar($value) ? (string) $value : '';
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? $value;

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function title(mixed $value): string
    {
        $clean = $this->clean($value);
        if ($clean === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($clean), MB_CASE_TITLE, 'UTF-8');
    }

    private function cleanIdentifier(mixed $value): string
    {
        if (is_float($value) && floor($value) === $value) {
            return (string) (int) $value;
        }

        return $this->clean($value);
    }

    private function normalizedCompany(string $company): string
    {
        $value = mb_strtoupper($company);
        $value = preg_replace('/\b(PRIVATE LIMITED|PVT\.? LTD\.?|PVT\.? LIMITED|LIMITED|LTD\.?)\b/u', ' ', $value) ?? $value;

        return trim((string) preg_replace('/[^A-Z0-9]+/u', ' ', $value));
    }

    private function normalizedName(string $name): string
    {
        return trim((string) preg_replace('/[^\pL\pN]+/u', ' ', mb_strtoupper($name)));
    }

    private function normalizePhone(mixed $value): string
    {
        $phone = $this->clean($value);
        if ($phone === '') {
            return '';
        }
        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return ($hasPlus ? '+' : '').$digits;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return CarbonImmutable::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function nameParts(string $name): array
    {
        $parts = array_values(array_filter(preg_split('/\s+/u', trim($name)) ?: []));
        if (count($parts) < 2) {
            return ['first_name' => $this->title($parts[0] ?? ''), 'middle_name' => null, 'last_name' => null, 'parse_confidence' => 'low'];
        }

        return [
            'first_name' => $this->title(array_shift($parts)),
            'middle_name' => count($parts) > 1 ? $this->title(implode(' ', array_slice($parts, 0, -1))) : null,
            'last_name' => $this->title(end($parts)),
            'parse_confidence' => 'heuristic_reviewable',
        ];
    }
}
