<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellerProductDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'document_type' => ['nullable', Rule::in(['datasheet', 'certificate', 'manual'])],
            'source_url' => ['nullable', 'url', 'max:500'],
            'file_path' => ['nullable', 'string', 'max:500'],
            'mime_type' => ['nullable', 'string', 'max:120'],
            'file_size' => ['nullable', 'integer', 'min:0', 'max:52428800'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
