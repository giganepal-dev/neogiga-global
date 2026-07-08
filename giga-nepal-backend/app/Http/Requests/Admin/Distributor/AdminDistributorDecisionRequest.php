<?php

namespace App\Http\Requests\Admin\Distributor;

use Illuminate\Foundation\Http\FormRequest;

class AdminDistributorDecisionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['reason' => ['sometimes', 'nullable', 'string', 'max:2000']];
    }
}
