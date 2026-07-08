<?php

namespace App\Http\Requests\Distributor;

use Illuminate\Foundation\Http\FormRequest;

class DistributorLeadStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:190'],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:190'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'company' => ['sometimes', 'nullable', 'string', 'max:190'],
            'estimated_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
