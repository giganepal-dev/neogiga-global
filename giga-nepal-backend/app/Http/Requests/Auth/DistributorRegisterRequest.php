<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DistributorRegisterRequest extends FormRequest
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
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email', 'unique:distributors,email'],
            'phone' => ['required', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'max:120'],
            'country_id' => ['nullable', 'integer', 'min:1'],
            'distributor_type' => ['required', Rule::in(['country_distributor', 'regional_distributor', 'city_distributor', 'institutional_distributor', 'reseller', 'service_partner', 'affiliate_distributor'])],
            'registration_number' => ['nullable', 'string', 'max:120'],
            'tax_number' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
