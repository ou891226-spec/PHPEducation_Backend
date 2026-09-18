<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KnowledgeCard extends Model
{
    protected $fillable = [
        'unit_id',
        'course_id',
        'title',
        'type',
        'content',
        'example',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'knowledge_card_unit')
            ->withTimestamps();
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'question_knowledge_cards')
            ->withTimestamps();
    }

    public function primaryUnit(): ?Unit
    {
        return $this->unit ?? $this->units->first();
    }
}
