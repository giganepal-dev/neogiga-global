<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SellerSupportTicketStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:3', 'max:190'],
            'category' => ['sometimes', 'string', 'max:80'],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }
}
