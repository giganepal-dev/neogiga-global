<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SellerMarketplaceApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'marketplace_id' => ['required', 'integer', 'exists:marketplaces,id'],
            'application_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
