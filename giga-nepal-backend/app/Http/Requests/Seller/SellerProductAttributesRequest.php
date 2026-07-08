<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SellerProductAttributesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'attributes' => ['required', 'array'],
            'attributes.*' => ['nullable'],
            'package_includes' => ['nullable', 'array'],
            'use_cases' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'search_keywords' => ['nullable', 'string', 'max:3000'],
            'safety_certification' => ['nullable', 'string', 'max:180'],
            'compliance_certification' => ['nullable', 'string', 'max:180'],
        ];
    }
}
