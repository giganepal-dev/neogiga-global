<?php

namespace App\Http\Requests\Admin\Bom;

use Illuminate\Foundation\Http\FormRequest;

class AdminBomItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:180'],
            'required_or_optional' => ['nullable', 'in:required,optional'],
            'quantity' => ['nullable', 'numeric', 'min:0.001'],
            'reason' => ['nullable', 'string'],
            'substitute_allowed' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
