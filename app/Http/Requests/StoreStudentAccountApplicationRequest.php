<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 教師以 Excel 送出學生名冊（學號、姓名）。
 */
class StoreStudentAccountApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tid' => ['required', 'integer', 'exists:teachers,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'file' => ['required', 'file'],
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
