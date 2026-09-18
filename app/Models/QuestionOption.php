<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
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
