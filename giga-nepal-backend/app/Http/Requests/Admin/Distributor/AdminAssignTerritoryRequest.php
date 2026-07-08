<?php

namespace App\Http\Requests\Admin\Distributor;

use Illuminate\Foundation\Http\FormRequest;

class AdminAssignTerritoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'territory_name' => ['required', 'string', 'max:190'],
            'country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'region_id' => ['sometimes', 'nullable', 'integer'],
            'city_id' => ['sometimes', 'nullable', 'integer'],
            'exclusive' => ['sometimes', 'boolean'],
            'can_manage_downlines' => ['sometimes', 'boolean'],
        ];
    }
}
