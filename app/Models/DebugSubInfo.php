<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebugSubInfo extends Model
{
    protected $table = 'debug_sub_info';

    protected $fillable = [
        'question_id',
        'code_line',
        'answer',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'code_line' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
