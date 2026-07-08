<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SellerProductWarrantyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'warranty_type' => ['nullable', 'string', 'max:120'],
            'warranty_period' => ['nullable', 'string', 'max:120'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'claim_requirements' => ['nullable', 'string', 'max:5000'],
            'return_policy' => ['nullable', 'string', 'max:5000'],
            'country_of_origin' => ['nullable', 'string', 'max:120'],
        ];
    }
}
