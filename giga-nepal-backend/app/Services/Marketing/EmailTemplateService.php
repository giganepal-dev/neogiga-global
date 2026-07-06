<?php

namespace App\Services\Marketing;

class EmailTemplateService
{
    public const VARIABLES = ['customer_name','email','phone','country','region','marketplace_name','order_number','order_total','currency','cart_items','product_name','category_name','tracking_number','invoice_url','unsubscribe_url','otp_code','expiry_minutes'];

    public function render(?string $body, array $vars): string
    {
        $body ??= '';
        foreach ($vars as $key => $value) $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        return $body;
    }
}
