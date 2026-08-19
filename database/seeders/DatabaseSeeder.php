<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public const TEST_PASSWORD = 'Password123!';

    public function run(): void
    {
        $password = Hash::make(self::TEST_PASSWORD);

        Admin::query()->create([
            'account' => 'admin@school.edu.tw',
            'password' => $password,
        ]);

        $teacherA = Teacher::query()->create([
            'account' => 'teacher@school.edu.tw',
            'password' => $password,
            'name' => '許老師',
            'email' => 'teacher@school.edu.tw',
        ]);

        $teacherB = Teacher::query()->create([
            'account' => 'teacher2@school.edu.tw',
            'password' => $password,
            'name' => '陳老師',
            'email' => 'teacher2@school.edu.tw',
        ]);

        Student::query()->create([
            'password' => $password,
            'student_no' => '1411131000',
            'name' => '王小明',
            'email' => Student::emailFromStudentNo('1411131000'),
        ]);

        Course::query()->create([
            'teacher_id' => $teacherB->id,
            'name' => '網際系統設計 (資應)',
            'semester' => '115-1',
        ]);

        Course::query()->create([
            'teacher_id' => $teacherB->id,
            'name' => '網際系統設計 (資管)',
            'semester' => '115-1',
        ]);
    }
}
