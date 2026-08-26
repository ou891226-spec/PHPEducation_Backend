<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\Auth\Authenticatable;

class DashboardService
{
    public function __construct(
        private readonly UserFormatterService $userFormatter,
    ) {}

    public function show(Authenticatable $authenticatable): array
    {
        $payload = [
            'user' => $this->userFormatter->format($authenticatable),
        ];

        if ($authenticatable instanceof Teacher) {
            return array_merge($payload, [
                'courses' => $this->formatCourses(
                    Course::query()
                        ->where('teacher_id', $authenticatable->id)
                        ->orderByDesc('semester')
                        ->orderByDesc('id')
                        ->get()
                ),
            ]);
        }

        if ($authenticatable instanceof Student) {
            return array_merge($payload, [
                'courses' => $this->formatCourses(
                    $authenticatable->courses()
                        ->orderByDesc('courses.semester')
                        ->orderByDesc('courses.id')
                        ->get()
                ),
            ]);
        }

        if ($authenticatable instanceof Admin) {
            return array_merge($payload, [
                'pending_count' => 0,
            ]);
        }

        return $payload;
    }

    /**
     * @param  iterable<int, Course>  $courses
     * @return array<int, array<string, mixed>>
     */
    private function formatCourses(iterable $courses): array
    {
        $formatted = [];

        foreach ($courses as $course) {
            $formatted[] = [
                'id' => $course->id,
                'name' => $course->name,
                'description' => $course->description,
                'semester' => $course->semester,
                'class_name' => $course->class_name,
                'teacher_id' => $course->teacher_id,
            ];
        }

        return $formatted;
    }
}
