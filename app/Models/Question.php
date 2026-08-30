<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    public const TYPE_CHOICE = 'choice';

    public const TYPE_TRUE_FALSE = 'true_false';

    public const TYPE_FILL = 'fill';

    public const TYPE_DEBUG = 'debug';

    public const TYPE_INTERPRET = 'interpret';

    public const TYPE_CODING = 'coding';

    public const TYPES = [
        self::TYPE_CHOICE,
        self::TYPE_TRUE_FALSE,
        self::TYPE_FILL,
        self::TYPE_DEBUG,
        self::TYPE_INTERPRET,
        self::TYPE_CODING,
    ];

    public const OPTION_TYPES = [
        self::TYPE_CHOICE,
        self::TYPE_TRUE_FALSE,
    ];

    public const SUB_ANSWER_TYPES = [
        self::TYPE_FILL,
        self::TYPE_DEBUG,
        self::TYPE_INTERPRET,
    ];

    protected $fillable = [
        'course_id',
        'teacher_id',
        'title',
        'type',
        'question_content',
        'bloom_id',
        'description',
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

    public function bloom(): BelongsTo
    {
        return $this->belongsTo(Bloom::class, 'bloom_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function subAnswers(): HasMany
    {
        return $this->hasMany(QuestionSubAnswer::class)->orderBy('sub_id');
    }

    public function usesOptions(): bool
    {
        return in_array($this->type, self::OPTION_TYPES, true);
    }

    public function usesSubAnswers(): bool
    {
        return in_array($this->type, self::SUB_ANSWER_TYPES, true);
    }

    public function records(): HasMany
    {
        return $this->hasMany(QuestionRecord::class);
    }
}
