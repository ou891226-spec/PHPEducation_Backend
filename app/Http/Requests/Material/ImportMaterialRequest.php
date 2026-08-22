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
            'file' => ['required', 'file', 'max:10240'],
        ];
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
