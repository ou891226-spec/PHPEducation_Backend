<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuestionRecord extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CORRECT = 'correct';

    public const STATUS_WRONG = 'wrong';

    protected $fillable = [
        'student_id',
        'question_id',
        'result',
        'question_mapping_id',
        'system_status',
        'teacher_status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function mapping(): BelongsTo
    {
        return $this->belongsTo(QuestionBloomSoloMapping::class, 'question_mapping_id');
    }

    public function aiFeedback(): HasOne
    {
        return $this->hasOne(AiFeedback::class, 'record_id');
    }
}
