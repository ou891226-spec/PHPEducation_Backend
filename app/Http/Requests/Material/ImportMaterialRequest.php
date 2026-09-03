<?php

namespace App\Http\Requests\Material;

use Illuminate\Foundation\Http\FormRequest;

class ImportMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file'],
            'overwrite' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('topic')) {
            $this->merge([
                'topic' => trim((string) $this->input('topic')),
            ]);
        }

        if ($this->has('overwrite')) {
            $this->merge([
                'overwrite' => filter_var($this->input('overwrite'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $file = $this->file('file');
            if ($file !== null && strtolower($file->getClientOriginalExtension()) !== 'xlsx') {
                $validator->errors()->add('file', '請上傳 xlsx 檔');
            }
        });
    }
}
