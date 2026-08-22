<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 學生物件模型
 */
class StudentApplicationItems extends Model
{
    //
    protected $fillable = [
        'application_id',
        'student_no',
        'name',
        'email',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(StudentApplications::class, 'application_id');
    }
}
