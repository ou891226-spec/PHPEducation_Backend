<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * 學生正式帳號模型
 */
class Student extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'password',
        'student_no',
        'name',
        'class_name',
        'email',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * 關聯：學生修習的多門課程
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * 輔助方法：根據學號產生校園 Email 地址
     * 若已為 Email 格式則直接返回，否則轉為 s[學號]@nutc.edu.tw
     *
     * @param string $studentNoOrEmail
     * @return string
     */
    public static function emailFromStudentNo(string $studentNoOrEmail): string
    {
        if (str_contains($studentNoOrEmail, '@')) {
            return $studentNoOrEmail;
        }

        $studentNo = ltrim($studentNoOrEmail, 'Ss');

        return 's'.$studentNo.'@nutc.edu.tw';
    }

    /**
     * 關聯： 學生與課程之間的多對多關聯，透過 enrollments 資料表
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments');
    }

    public function questionRecords(): HasMany
    {
        return $this->hasMany(QuestionRecord::class);
    }
}
