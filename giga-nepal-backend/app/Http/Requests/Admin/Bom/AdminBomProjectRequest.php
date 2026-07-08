<?php

namespace App\Http\Requests\Admin\Bom;

use Illuminate\Foundation\Http\FormRequest;

class AdminBomProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $titleRule = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'bom_project_category_id' => ['nullable', 'integer', 'min:1'],
            'marketplace_id' => ['nullable', 'integer', 'min:1'],
            'country_id' => ['nullable', 'integer', 'min:1'],
            'title' => [$titleRule, 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200'],
            'difficulty' => ['nullable', 'string', 'max:40'],
            'estimated_build_time' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'safety_notes' => ['nullable', 'string'],
            'required_tools' => ['nullable', 'array'],
            'is_public' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:40'],
            'seo_meta' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
