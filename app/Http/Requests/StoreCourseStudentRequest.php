<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 教師補學生（可一次多筆）。班級取自課程。
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
            'students.*.name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rows = $this->input('students');

        if (! is_array($rows) || $rows === []) {
            $studentNo = $this->input('student_no');
            $name = $this->input('name');
            if ($studentNo !== null || $name !== null) {
                $rows = [[
                    'student_no' => $studentNo,
                    'name' => $name,
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

            if ($studentNo === '' && $name === '') {
                continue;
            }

            $students[] = [
                'student_no' => $studentNo,
                'name' => $name,
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
