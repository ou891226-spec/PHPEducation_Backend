<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodingSubInfo extends Model
{
    protected $table = 'coding_sub_info';

    protected $fillable = [
        'question_id',
        'ref_answer',
        'ref_output',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
