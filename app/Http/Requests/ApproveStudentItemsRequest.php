<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveStudentItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // 相容舊前端：只傳 course_id 時轉成 course_ids
        if (! $this->exists('course_ids') && $this->filled('course_id')) {
            $this->merge([
                'course_ids' => [(int) $this->input('course_id')],
            ]);
        }

        // 相容：有 course_id 但沒 source_course_id 時，視為申請來源課
        if (! $this->filled('source_course_id') && $this->filled('course_id') && ! $this->filled('item_ids')) {
            $this->merge([
                'source_course_id' => (int) $this->input('course_id'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'distinct', 'exists:courses,id'],
            // 新流程：指定申請來源課，後端自動抓該課全部 pending
            'source_course_id' => ['required_without:item_ids', 'integer', 'exists:courses,id'],
            // 舊流程：手動指定明細（可省略）
            'item_ids' => ['required_without:source_course_id', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'exists:student_application_items,id'],
            'course_id' => ['sometimes', 'integer', 'exists:courses,id'],
        ];
    }
}
