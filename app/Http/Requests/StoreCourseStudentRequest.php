<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 教師單筆補學生（學號、姓名）。班級取自課程。
 */
class StoreCourseStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_no' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $studentNo = preg_replace('/\s+/u', '', (string) $this->input('student_no')) ?? '';
        if (preg_match('/^[sS](\d+)$/', $studentNo, $matches) === 1) {
            $studentNo = $matches[1];
        }

        $this->merge([
            'student_no' => $studentNo,
            'name' => trim((string) $this->input('name')),
        ]);
    }
}
