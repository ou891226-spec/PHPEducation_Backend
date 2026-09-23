<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 教師修改課程名冊中的一位學生（學號、姓名、信箱）。
 */
class UpdateCourseStudentRequest extends FormRequest
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
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $studentNo = $this->normalizeStudentNo((string) ($this->input('student_no') ?? $this->input('studentNo') ?? ''));
        $name = trim((string) ($this->input('name') ?? ''));

        $payload = [
            'student_no' => $studentNo,
            'name' => $name,
        ];

        if ($this->exists('email')) {
            $payload['email'] = strtolower(trim((string) $this->input('email')));
        }

        $this->merge($payload);
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
