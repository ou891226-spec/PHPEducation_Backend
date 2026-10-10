<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeedback extends Model
{
    protected $table = 'ai_feedback';

    protected $fillable = [
        'question_record_id',
        'feedback_content',
    ];

    public function questionRecord(): BelongsTo
    {
        return $this->belongsTo(QuestionRecord::class, 'question_record_id');
    }
}
