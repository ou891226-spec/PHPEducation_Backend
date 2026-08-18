<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'password',
        'student_no',
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

    public static function emailFromStudentNo(string $studentNoOrEmail): string
    {
        if (str_contains($studentNoOrEmail, '@')) {
            return $studentNoOrEmail;
        }

        $studentNo = ltrim($studentNoOrEmail, 'Ss');

        return 's'.$studentNo.'@nutc.edu.tw';
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments');
    }
}
