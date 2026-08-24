<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionKnowledgeCard extends Model
{
    protected $fillable = [
        'question_id',
        'knowledge_card_id',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function knowledgeCard(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCard::class);
    }
}
