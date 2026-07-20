<?php

namespace App\Http\Requests\B2B;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class B2BApplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = array_keys(config('b2b_institutional.account_types', ['corporate' => 'Corporate']));
        $type = (string) $this->input('type', 'corporate');

        return [
            'name' => ['required', 'string', 'min:2', 'max:190'],
            'type' => ['required', 'string', Rule::in($types)],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'pan_vat_number' => ['sometimes', 'nullable', 'string', 'max:80'],
            'country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'marketplace_id' => ['sometimes', 'nullable', 'integer', 'exists:marketplaces,id'],
            'document_company_reg' => [Rule::requiredIf(in_array($type, ['corporate', 'government', 'school', 'ngo', 'hospital'], true)), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_tax_certificate' => [Rule::requiredIf(in_array($type, ['corporate', 'government', 'hospital'], true)), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_institutional_id' => [Rule::requiredIf(in_array($type, ['government', 'school', 'ngo', 'hospital'], true)), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
