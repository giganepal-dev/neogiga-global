<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:180'],
            'contact_person' => ['required', 'string', 'max:140'],
            'email' => ['required', 'email:rfc,dns', 'max:180'],
            'phone' => ['required', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'country_id' => ['nullable', 'integer', 'min:1'],
            'operating_scope' => ['sometimes', Rule::in(['country', 'global'])],
            'region_id' => ['nullable', 'integer', 'min:1'],
            'city_id' => ['nullable', 'integer', 'min:1'],
            'business_type' => ['required', 'string', 'max:80'],
            'seller_type' => ['required', 'string', 'max:80'],
            'product_categories' => ['nullable', 'array'],
            'product_categories.*' => ['string', 'max:120'],
            'brands_carried' => ['nullable', 'array'],
            'brands_carried.*' => ['string', 'max:120'],
            'has_existing_inventory' => ['nullable', 'boolean'],
            'has_physical_store' => ['nullable', 'boolean'],
            'monthly_order_capacity' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'url', 'max:255'],
            'message' => ['nullable', 'string', 'max:3000'],
            'source' => ['nullable', 'string', 'max:80'],
        ];
    }
}
