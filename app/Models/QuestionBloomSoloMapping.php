<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionBloomSoloMapping extends Model
{
    protected $fillable = [
        'question_id',
        'bloom_id',
        'solo_id',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function bloom(): BelongsTo
    {
        return $this->belongsTo(Bloom::class, 'bloom_id');
    }

    public function solo(): BelongsTo
    {
        return $this->belongsTo(Solo::class, 'solo_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(QuestionRecord::class, 'question_mapping_id');
    }
}
