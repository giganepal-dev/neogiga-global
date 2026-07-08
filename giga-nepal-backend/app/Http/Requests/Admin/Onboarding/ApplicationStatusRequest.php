<?php

namespace App\Http\Requests\Admin\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'contacted', 'approved_for_onboarding', 'rejected', 'archived'])],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
