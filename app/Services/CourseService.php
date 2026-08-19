<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CourseService
{
    public function listForTeacher(Teacher $teacher): array
    {
        return Course::query()
            ->where('teacher_id', $teacher->id)
            ->orderByDesc('semester')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Course $course) => $this->formatCourse($course))
            ->all();
    }

    public function create(Teacher $teacher, array $data): array
    {
        $course = Course::query()->create([
            'teacher_id' => $teacher->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'semester' => $data['semester'],
        ]);

        return $this->formatCourse($course);
    }

    public function findForTeacher(Teacher $teacher, int $courseId): array
    {
        $course = $this->findOwnedCourse($teacher, $courseId);

        return $this->formatCourse($course);
    }

    public function update(Teacher $teacher, int $courseId, array $data): array
    {
        $course = $this->findOwnedCourse($teacher, $courseId);

        $course->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'semester' => $data['semester'],
        ]);

        return $this->formatCourse($course->fresh());
    }

    public function delete(Teacher $teacher, int $courseId): void
    {
        $course = $this->findOwnedCourse($teacher, $courseId);
        $course->delete();
    }

    private function findOwnedCourse(Teacher $teacher, int $courseId): Course
    {
        $course = Course::query()
            ->where('teacher_id', $teacher->id)
            ->where('id', $courseId)
            ->first();

        if ($course === null) {
            throw new ModelNotFoundException();
        }

        return $course;
    }

    private function formatCourse(Course $course): array
    {
        return [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'semester' => $course->semester,
            'teacher_id' => $course->teacher_id,
        ];
    }
}
