<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminGenericSuggestionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'source_product_id' => ['nullable', 'integer', 'min:1'],
            'suggested_product_id' => ['nullable', 'integer', 'min:1'],
            'suggested_name' => ['nullable', 'string', 'max:180'],
            'suggestion_type' => ['required', Rule::in(['equivalent', 'compatible', 'upgrade', 'accessory', 'replacement', 'required', 'optional', 'related'])],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'reason' => ['nullable', 'string', 'max:3000'],
            'marketplace_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
