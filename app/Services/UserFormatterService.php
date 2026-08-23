<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\Auth\Authenticatable;

class UserFormatterService
{
    public function format(Authenticatable $authenticatable): array
    {
        if ($authenticatable instanceof Admin) {
            return [
                'id' => $authenticatable->id,
                'account' => $authenticatable->account,
                'name' => '系統管理員',
                'role' => 'admin',
            ];
        }

        if ($authenticatable instanceof Teacher) {
            return [
                'id' => $authenticatable->id,
                'account' => $authenticatable->account,
                'name' => $authenticatable->name,
                'role' => 'teacher',
            ];
        }

        if ($authenticatable instanceof Student) {
            return [
                'id' => $authenticatable->id,
                'account' => $authenticatable->email,
                'student_no' => $authenticatable->student_no,
                'name' => $authenticatable->name,
                'class_name' => $authenticatable->class_name,
                'role' => 'student',
            ];
        }

        return [
            'id' => 0,
            'account' => '',
            'name' => '',
            'role' => '',
        ];
    }
}
