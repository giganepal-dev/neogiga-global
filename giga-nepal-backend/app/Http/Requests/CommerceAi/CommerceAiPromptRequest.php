<?php

namespace App\Http\Requests\CommerceAi;

use Illuminate\Foundation\Http\FormRequest;

class CommerceAiPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:3', 'max:1200'],
            'session_key' => ['nullable', 'string', 'max:120'],
        ];
    }
}
