<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionRecordSub extends Model
{
    protected $fillable = [
        'question_record_id',
        'sub_id',
        'answer',
        'is_right',
        'solo',
    ];

    protected function casts(): array
    {
        return [
            'sub_id' => 'integer',
            'is_right' => 'boolean',
            'solo' => 'integer',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(QuestionRecord::class);
    }
}
