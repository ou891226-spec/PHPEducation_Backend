<?php

namespace App\Http\Requests\Question;

use App\Models\QuestionRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewQuestionRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $bloomId = $this->input('bloom_id');
        if (is_string($bloomId)) {
            $this->merge([
                'bloom_id' => strtoupper(trim($bloomId)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'solo' => ['required', 'integer', Rule::in(range(1, 5))],// 修正 SOLO 上限值
            'bloom_id' => ['nullable', 'string', 'exists:bloom,id'],
        ];
    }
}
