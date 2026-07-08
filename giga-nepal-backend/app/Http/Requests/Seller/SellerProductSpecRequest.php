<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SellerProductSpecRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'spec_group_id' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:160'],
            'value' => ['required', 'string', 'max:500'],
            'unit' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
            'is_filterable' => ['nullable', 'boolean'],
        ];
    }
}
