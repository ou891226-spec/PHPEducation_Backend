<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherApplication extends Model
{
    //
    public $timestamps = false;
    
    protected $fillable = [
        'name',
        'email',
        'reason',
        'status',
        'timestamps',
    ];
}
