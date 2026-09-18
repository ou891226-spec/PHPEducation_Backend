<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionRecord extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CORRECT = 'correct';

    public const STATUS_WRONG = 'wrong';

    public const SOLO_WRONG = 1;

    public const SOLO_CORRECT = 2;

    public const SOLO_PARTIAL = 2;

    public const SOLO_ALL_CORRECT = 3;

    protected $fillable = [
        'student_id',
        'question_id',
        'result',
        'system_status',
        'teacher_status',
        'solo',
        'bloom_id',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function bloom(): BelongsTo
    {
        return $this->belongsTo(Bloom::class, 'bloom_id');
    }

    public function subs(): HasMany
    {
        return $this->hasMany(QuestionRecordSub::class)->orderBy('sub_id');
    }
}
