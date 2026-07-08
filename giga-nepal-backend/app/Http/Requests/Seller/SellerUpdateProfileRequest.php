<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SellerUpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'website' => ['sometimes', 'nullable', 'url', 'max:190'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'about' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'business_type' => ['sometimes', 'nullable', 'string', 'max:120'],
            'return_policy' => ['sometimes', 'nullable', 'string', 'max:500'],
            'warranty_policy' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
