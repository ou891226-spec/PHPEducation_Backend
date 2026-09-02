<?php

namespace App\Http\Requests\Material;

use Illuminate\Foundation\Http\FormRequest;

class KnowledgeCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'content' => ['required', 'string'],
            'example' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '請填寫知識卡名稱',
            'content.required' => '請填寫圖文內容',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('title') && $this->filled('name')) {
            $this->merge(['title' => $this->input('name')]);
        }

        if (! $this->exists('example') && $this->exists('code_example')) {
            $this->merge(['example' => $this->input('code_example')]);
        }
    }
}
