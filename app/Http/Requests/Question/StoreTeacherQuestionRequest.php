<?php

namespace App\Http\Requests\Question;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTeacherQuestionRequest extends FormRequest
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
        $optionType = in_array($this->input('type'), Question::OPTION_TYPES, true);
        $subType = in_array($this->input('type'), Question::SUB_ANSWER_TYPES, true);
        $trueFalse = $this->input('type') === Question::TYPE_TRUE_FALSE;

        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(Question::TYPES)],
            'question_content' => ['required', 'string'],
            'bloom_id' => ['required', 'string', 'exists:bloom,id'],
            'description' => ['nullable', 'string'],
            'show_example' => ['sometimes', 'boolean'],
            'starter_code' => ['nullable', 'string'],
            'expected_output' => ['nullable', 'string'],
            'reference_answer' => ['nullable', 'string'],
            'knowledge_card_ids' => ['required', 'array', 'min:1'],
            'knowledge_card_ids.*' => ['integer', 'distinct', 'exists:knowledge_cards,id'],
            'options' => array_filter([
                Rule::requiredIf($optionType),
                Rule::prohibitedIf(! $optionType),
                'array',
                $optionType ? 'min:2' : null,
                $trueFalse ? 'size:2' : null,
            ]),
            'options.*.title' => [$optionType ? 'required' : 'nullable', 'string', 'max:255'],
            'options.*.description' => ['nullable', 'string'],
            'options.*.is_answer' => [$optionType ? 'required' : 'nullable', 'boolean'],
            'sub_answers' => array_filter([
                Rule::requiredIf($subType),
                Rule::prohibitedIf(! $subType),
                'array',
                $subType ? 'min:1' : null,
            ]),
            'sub_answers.*.sub_id' => [$subType ? 'required' : 'nullable', 'integer', 'min:1', 'distinct'],
            'sub_answers.*.answer' => [$subType ? 'required' : 'nullable', 'string'],
            'sub_answers.*.description' => ['nullable', 'string'],
            'sub_answers.*.solo' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! in_array($this->input('type'), Question::OPTION_TYPES, true)) {
                return;
            }

            $correct = collect($this->input('options', []))
                ->filter(fn ($option) => is_array($option) && $this->isTrue($option['is_answer'] ?? false))
                ->count();

            if ($correct !== 1) {
                $validator->errors()->add('options', '選擇／是非題必須剛好一個正解。');
            }
        });
    }

    private function isTrue(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bloom_id' => 'Bloom 編碼',
            'knowledge_card_ids' => '知識卡',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'knowledge_card_ids.required' => '請選擇知識卡。若下拉是空的，代表這門課還沒有已發布教材。',
            'knowledge_card_ids.min' => '請至少選一張知識卡。',
            'bloom_id.exists' => 'Bloom 編碼請用老師格式，例如 B11、B13、B42（大小寫皆可）。',
        ];
    }
}
