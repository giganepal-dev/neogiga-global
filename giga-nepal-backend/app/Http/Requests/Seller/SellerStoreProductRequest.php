<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SellerStoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:190'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:120'],
            'vendor_sku' => ['sometimes', 'nullable', 'string', 'max:120'],
            'marketplace_id' => ['sometimes', 'nullable', 'integer', 'exists:marketplaces,id'],
            'description' => ['sometimes', 'nullable', 'string', 'max:8000'],
            'payload' => ['sometimes', 'array'],
        ];
    }
}
