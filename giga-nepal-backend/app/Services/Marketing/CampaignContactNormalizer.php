<?php

namespace App\Services\Marketing;

class CampaignContactNormalizer
{
    /**
     * Normalize raw row data to standard format.
     */
    public function normalize(array $row): array
    {
        return [
            'email' => $this->normalizeEmail($row['email'] ?? null),
            'first_name' => $this->normalizeString($row['first_name'] ?? $row['firstname'] ?? null),
            'last_name' => $this->normalizeString($row['last_name'] ?? $row['lastname'] ?? null),
            'full_name' => $this->normalizeString($row['full_name'] ?? $row['fullname'] ?? $row['name'] ?? null),
            'company_name' => $this->normalizeString($row['company_name'] ?? $row['company'] ?? $row['organization'] ?? null),
            'phone' => $this->normalizePhone($row['phone'] ?? $row['phone_number'] ?? null),
            'job_title' => $this->normalizeString($row['job_title'] ?? $row['title'] ?? null),
            'country_code' => $this->normalizeCountryCode($row['country_code'] ?? $row['country'] ?? null),
            'language' => $this->normalizeLanguage($row['language'] ?? $row['lang'] ?? null),
            'subscriber_type' => $this->normalizeSubscriberType($row['subscriber_type'] ?? $row['type'] ?? null),
        ];
    }

    /**
     * Validate normalized data.
     */
    public function validate(array $normalized): array
    {
        $errors = [];
        $warnings = [];

        // Email is required
        if (empty($normalized['email'])) {
            $errors[] = ['field' => 'email', 'code' => 'required', 'message' => 'Email is required.'];
        } elseif (!filter_var($normalized['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['field' => 'email', 'code' => 'invalid', 'message' => 'Invalid email format.'];
        }

        // Validate country code if provided
        if (!empty($normalized['country_code']) && strlen($normalized['country_code']) !== 2) {
            $warnings[] = ['field' => 'country_code', 'code' => 'invalid_length', 'message' => 'Country code should be 2 characters.'];
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function normalizeEmail(?string $email): ?string
    {
        if (empty($email)) return null;
        return mb_strtolower(trim($email));
    }

    private function normalizeString(?string $value): ?string
    {
        if (empty($value)) return null;
        return trim($value);
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) return null;
        return preg_replace('/[^\d+\-\(\)\s]/', '', trim($phone));
    }

    private function normalizeCountryCode(?string $code): ?string
    {
        if (empty($code)) return null;
        $code = strtoupper(trim($code));
        return strlen($code) === 2 ? $code : null;
    }

    private function normalizeLanguage(?string $lang): ?string
    {
        if (empty($lang)) return 'en';
        return strtolower(trim(substr($lang, 0, 2)));
    }

    private function normalizeSubscriberType(?string $type): ?string
    {
        if (empty($type)) return 'lead';
        $type = strtolower(trim($type));
        $validTypes = ['personal_customer', 'institutional_customer', 'reseller', 'distributor',
            'manufacturer', 'supplier', 'educational_institution', 'government_buyer',
            'industry', 'engineer', 'maker', 'newsletter_subscriber', 'lead', 'other'];
        return in_array($type, $validTypes) ? $type : 'lead';
    }
}
