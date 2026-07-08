<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellerRegisterRequest extends FormRequest
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
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email', 'unique:vendors,email'],
            'phone' => ['required', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'max:120'],
            'country_id' => ['nullable', 'integer', 'min:1'],
            'website' => ['nullable', 'url', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:80'],
            'vendor_type' => ['nullable', Rule::in(['manufacturer', 'distributor', 'reseller', 'retailer', 'service_provider', 'importer', 'exporter'])],
            'registration_number' => ['nullable', 'string', 'max:120'],
            'tax_number' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
