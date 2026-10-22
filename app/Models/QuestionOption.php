<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    // 干擾選項未設定 SOLO 時預設 S1；正解未設定時依 Bloom 給定初始值。
    public const SOLO_DEFAULT = 1;

    // 相容既有程式碼常數；實際作答紀錄一律讀取該選項的 solo 欄位。
    public const SOLO_CORRECT = 2;

    public const SOLO_WRONG = 1;

    protected $fillable = [
        'question_id',
        'title',
        'description',
        'is_answer',
        'solo',
    ];

    protected function casts(): array
    {
        return [
            'is_answer' => 'boolean',
            'solo' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
