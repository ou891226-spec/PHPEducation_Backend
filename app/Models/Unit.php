<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'chapter_id',
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function knowledgeCards(): HasMany
    {
        return $this->hasMany(KnowledgeCard::class)->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
