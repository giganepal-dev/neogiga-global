<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SellerOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:processing,shipped,fulfilled,delivered,cancelled'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
