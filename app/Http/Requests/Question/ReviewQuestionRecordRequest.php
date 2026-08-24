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

    public function rules(): array
    {
        return [
            'teacher_status' => ['required', 'string', Rule::in([
                QuestionRecord::STATUS_CORRECT,
                QuestionRecord::STATUS_WRONG,
            ])],
        ];
    }
}
