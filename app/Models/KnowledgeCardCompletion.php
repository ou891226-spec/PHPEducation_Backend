<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeCardCompletion extends Model
{
    public const METHOD_QUIZ = 'quiz';

    public const METHOD_READ = 'read';

    protected $fillable = [
        'student_id',
        'course_id',
        'knowledge_card_id',
        'method',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function knowledgeCard(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCard::class);
    }
}
