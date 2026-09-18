<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * 教師正式帳號模型
 */
class Teacher extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'account',
        'password',
        'name',
        'email',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
