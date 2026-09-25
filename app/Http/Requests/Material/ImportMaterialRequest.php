<?php

namespace App\Http\Requests\Material;

use App\Services\MaterialImportPlanner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'mode' => ['sometimes', 'nullable', Rule::in(MaterialImportPlanner::MODES)],
            'chapter_id' => ['nullable', 'integer', 'required_if:mode,'.MaterialImportPlanner::MODE_REPLACE],
            'fingerprint' => ['sometimes', 'nullable', 'string', 'size:64'],
            'overwrite' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'mode.in' => '匯入方式只能是 append、replace 或 overwrite',
            'chapter_id.required_if' => '替換章節時請選擇要替換的章節',
        ];
    }

    protected function prepareForValidation(): void
    {
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

    /**
     * 沒帶 mode 時相容舊版 overwrite=1。
     */
    public function importMode(): ?string
    {
        $mode = $this->validated('mode');
        if (is_string($mode) && $mode !== '') {
            return $mode;
        }

        return $this->boolean('overwrite') ? MaterialImportPlanner::MODE_OVERWRITE : null;
    }

    public function chapterId(): ?int
    {
        $chapterId = $this->validated('chapter_id');

        return $chapterId === null ? null : (int) $chapterId;
    }

    public function fingerprint(): ?string
    {
        $fingerprint = $this->validated('fingerprint');

        return is_string($fingerprint) && $fingerprint !== '' ? $fingerprint : null;
    }
}
