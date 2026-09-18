<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;

class StatsService
{
    /**
     * 全站老師、學生、課程數量。本學期以資料裡最新的 semester 為準。
     *
     * @return array{teacher_count: int, student_count: int, course_count: int, semester_course_count: int, semester: string|null}
     */
    public function counts(): array
    {
        $semester = Course::query()->max('semester');

        return [
            'teacher_count' => Teacher::query()->count(),
            'student_count' => Student::query()->count(),
            'course_count' => Course::query()->count(),
            'semester_course_count' => $semester === null
                ? 0
                : Course::query()->where('semester', $semester)->count(),
            'semester' => $semester,
        ];
    }
}
