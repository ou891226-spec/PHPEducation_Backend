<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 教師帳號申請模型
 */
class TeacherApplication extends Model
{
    //
    protected $fillable = [
        'name',
        'email',
        'reason',
        'status',
    ];
}
