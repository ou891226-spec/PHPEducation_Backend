<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    public const TYPE_CHOICE = 'choice';

    public const TYPE_DEBUG = 'debug';

    public const TYPE_CODING = 'coding';

    protected $fillable = [
        'course_id',
        'teacher_id',
        'title',
        'type',
        'question_content',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function knowledgeCards(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeCard::class, 'question_knowledge_cards')
            ->withTimestamps();
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function debugSubInfo(): HasOne
    {
        return $this->hasOne(DebugSubInfo::class);
    }

    public function codingSubInfo(): HasOne
    {
        return $this->hasOne(CodingSubInfo::class);
    }

    public function bloomSoloMappings(): HasMany
    {
        return $this->hasMany(QuestionBloomSoloMapping::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(QuestionRecord::class);
    }
}
