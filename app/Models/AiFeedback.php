<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeedback extends Model
{
    protected $table = 'ai_feedback';

    protected $fillable = [
        'record_id',
        'feedback_content',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(QuestionRecord::class, 'record_id');
    }
}
