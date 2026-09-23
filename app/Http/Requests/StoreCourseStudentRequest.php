<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 教師補學生（可一次多筆）。學號必填；姓名、信箱選填；班級取自課程。
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
            'students' => ['required', 'array', 'min:1', 'max:100'],
            'students.*.student_no' => ['required', 'string', 'max:50'],
            'students.*.name' => ['nullable', 'string', 'max:255'],
            'students.*.email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rows = $this->input('students');

        if (! is_array($rows) || $rows === []) {
            $studentNo = $this->input('student_no');
            $name = $this->input('name');
            $email = $this->input('email');
            if ($studentNo !== null || $name !== null || $email !== null) {
                $rows = [[
                    'student_no' => $studentNo,
                    'name' => $name,
                    'email' => $email,
                ]];
            } else {
                $rows = [];
            }
        }

        $students = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $studentNo = $this->normalizeStudentNo((string) ($row['student_no'] ?? $row['studentNo'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));

            if ($studentNo === '') {
                continue;
            }

            $students[] = [
                'student_no' => $studentNo,
                'name' => $name,
                'email' => $email !== '' ? $email : null,
            ];
        }

        $this->merge(['students' => $students]);
    }

    private function normalizeStudentNo(string $value): string
    {
        $studentNo = preg_replace('/\s+/u', '', $value) ?? '';
        if (preg_match('/^[sS](\d+)$/', $studentNo, $matches) === 1) {
            return $matches[1];
        }

        return $studentNo;
    }
}
