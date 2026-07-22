<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DistributorApplicationRequest extends FormRequest
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
            'distributor_type' => ['required', Rule::in(['country_distributor', 'regional_distributor', 'city_distributor', 'reseller', 'service_partner'])],
            'territory_interest' => ['nullable', 'string', 'max:180'],
            'current_business_categories' => ['nullable', 'array'],
            'current_business_categories.*' => ['string', 'max:120'],
            'existing_dealer_network' => ['nullable', 'boolean'],
            'warehouse_available' => ['nullable', 'boolean'],
            'monthly_capacity' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:3000'],
            'source' => ['nullable', 'string', 'max:80'],
        ];
    }
}
