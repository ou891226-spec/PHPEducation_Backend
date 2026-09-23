<?php

namespace App\Http\Requests\Material;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaterialUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'nullable', Rule::in(['draft', 'published'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '請填寫標題名稱',
            'status.in' => '單元狀態只能是 draft 或 published',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('name') && $this->filled('title')) {
            $this->merge(['name' => $this->input('title')]);
        }
    }
}
