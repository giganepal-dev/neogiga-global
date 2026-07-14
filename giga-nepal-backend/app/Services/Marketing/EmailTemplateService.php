<?php

namespace App\Services\Marketing;

class EmailTemplateService
{
    public const VARIABLES = ['first_name', 'contact_name', 'customer_name', 'company_name', 'email', 'phone', 'country', 'country_name', 'region', 'marketplace_name', 'marketplace_url', 'activation_url', 'verification_url', 'password_reset_url', 'order_number', 'order_date', 'order_status', 'order_total', 'currency', 'cart_items', 'product_name', 'category_name', 'tracking_number', 'tracking_url', 'invoice_number', 'invoice_url', 'rfq_number', 'quotation_number', 'quotation_url', 'support_url', 'unsubscribe_url', 'preferences_url', 'otp_code', 'expiry_minutes', 'current_year'];

    public function render(?string $body, array $vars): string
    {
        $body ??= '';
        foreach ($vars as $key => $value) {
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        }

        return $body;
    }
}
