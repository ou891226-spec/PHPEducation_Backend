<?php

namespace App\Models;

use App\Support\AnswerText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionSubAnswer extends Model
{
    // 每個填空格各自保存 SOLO；未設定時服務層沿用預設正解層級。
    public const SOLO_DEFAULT = 1;

    // 相容既有程式碼常數；每個子答案實際只保存單一 solo 欄位。
    public const SOLO_CORRECT = 2;

    public const SOLO_WRONG = 1;

    protected $fillable = [
        'question_id',
        'sub_id',
        'answer',
        'accepted_answers',
        'description',
        'solo',
    ];

    protected function casts(): array
    {
        return [
            'sub_id' => 'integer',
            'accepted_answers' => 'array',
            'solo' => 'integer',
        ];
    }

    /**
     * 標準答案加上老師另外登記、也算對的字串。
     *
     * @return list<string>
     */
    public function acceptableAnswers(): array
    {
        $extras = is_array($this->accepted_answers) ? $this->accepted_answers : [];

        return array_values(array_filter(
            [(string) $this->answer, ...$extras],
            fn (mixed $answer) => is_string($answer) && $answer !== '',
        ));
    }

    public function accepts(string $studentAnswer): bool
    {
        foreach ($this->acceptableAnswers() as $expected) {
            if (AnswerText::same($studentAnswer, $expected)) {
                return true;
            }
        }

        return false;
    }

    public function correctAnswerText(): string
    {
        return implode(' 或 ', $this->acceptableAnswers());
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
