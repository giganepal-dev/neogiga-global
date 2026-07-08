<?php

namespace App\Http\Requests\Bom;

use Illuminate\Foundation\Http\FormRequest;

class BomBuildCustomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'goal' => ['nullable', 'string', 'max:1000'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['nullable', 'integer', 'min:1'],
            'items.*.name' => ['required_with:items', 'string', 'max:180'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.001'],
        ];
    }
}
