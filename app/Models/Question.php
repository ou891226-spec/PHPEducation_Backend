<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    public const TYPE_CHOICE = 'choice';

    public const TYPE_DEBUG = 'debug';

    public const TYPE_CODING = 'coding';

    protected $fillable = [
        'unit_id',
        'teacher_id',
        'title',
        'type',
        'question_content',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
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
