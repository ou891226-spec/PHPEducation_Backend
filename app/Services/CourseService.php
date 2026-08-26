<?php

namespace App\Services;

use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(): array
    {
        return Course::query()
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
            'description' => $data['description'],
            'semester' => $data['semester'],
            'class_name' => $data['class_name'],
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
            'description' => $data['description'],
            'semester' => $data['semester'],
            'class_name' => $data['class_name'],
        ]);

        return $this->formatCourse($course->fresh());
    }

    public function delete(Teacher $teacher, int $courseId): void
    {
        $course = $this->findOwnedCourse($teacher, $courseId);

        DB::transaction(function () use ($course): void {
            KnowledgeCard::query()
                ->where(function (Builder $query) use ($course): void {
                    $query->whereHas(
                        'unit.chapter.topic',
                        fn (Builder $topicQuery) => $topicQuery->where('course_id', $course->id),
                    )->orWhere(function (Builder $detached) use ($course): void {
                        $detached->whereNull('unit_id')
                            ->whereHas('questions', fn (Builder $questionQuery) => $questionQuery->where('course_id', $course->id));
                    });
                })
                ->delete();

            $course->delete();
        });
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
            'class_name' => $course->class_name,
            'teacher_id' => $course->teacher_id,
        ];
    }
}
