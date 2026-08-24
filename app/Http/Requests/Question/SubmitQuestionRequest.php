<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'option_id' => ['sometimes', 'nullable', 'integer'],
            'code_line' => ['sometimes', 'nullable', 'integer'],
            'answer' => ['sometimes', 'nullable', 'string'],
            'code' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
