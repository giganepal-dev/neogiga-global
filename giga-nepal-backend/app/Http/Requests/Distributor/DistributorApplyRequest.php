<?php

namespace App\Http\Requests\Distributor;

use Illuminate\Foundation\Http\FormRequest;

class DistributorApplyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:190'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'type' => ['required', 'in:country_distributor,regional_distributor,city_distributor,institutional_distributor,reseller,affiliate_distributor,service_partner'],
            'country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'business_name' => ['sometimes', 'nullable', 'string', 'max:190'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
