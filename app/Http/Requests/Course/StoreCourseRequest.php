<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'semester' => ['required', 'string', 'max:50'],
            'class_name' => ['required', 'string', 'max:255'],
            'source_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'copy_materials' => ['sometimes', 'boolean'],
            'copy_questions' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $copyMaterials = $this->boolean('copy_materials');
            $copyQuestions = $this->boolean('copy_questions');
            $sourceCourseId = $this->input('source_course_id');

            if ($copyQuestions && ! $copyMaterials) {
                $validator->errors()->add(
                    'copy_questions',
                    '複製題目時必須同時複製教材（copy_materials）。',
                );
            }

            if (($copyMaterials || $copyQuestions) && $sourceCourseId === null) {
                $validator->errors()->add(
                    'source_course_id',
                    '複製教材或題目時必須指定來源課程。',
                );
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        $data['copy_materials'] = $this->boolean('copy_materials');
        $data['copy_questions'] = $this->boolean('copy_questions');

        return $data;
    }
}
