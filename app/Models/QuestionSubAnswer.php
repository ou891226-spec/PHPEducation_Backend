<?php

namespace App\Models;

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
        'description',
        'solo',
    ];

    protected function casts(): array
    {
        return [
            'sub_id' => 'integer',
            'solo' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
