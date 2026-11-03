<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'student_login_enabled',
    ];

    protected function casts(): array
    {
        return [
            'student_login_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'student_login_enabled' => true,
        ]);
    }

    public static function studentLoginEnabled(): bool
    {
        return static::current()->student_login_enabled;
    }
}
