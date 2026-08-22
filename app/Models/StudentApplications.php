<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 學生帳號申請模型
 */
class StudentApplications extends Model
{
    //
    protected $fillable = [
        'tid',
        'class_name',
        'status',
    ];
    
    /**
     * 關聯：此申請單包含的多筆學生明細
     */
    public function items(): HasMany
    {
        return $this->hasMany(StudentApplicationItems::class, 'application_id');
    }

    /**
     * 關聯：提出此申請單的教師
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'tid', 'id');
    }
}
