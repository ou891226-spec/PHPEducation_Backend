<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Student;
use App\Models\Topic;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StudentMaterialService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listTopics(Student $student, int $courseId): array
    {
        $course = $this->enrolledCourse($student, $courseId);

        return Topic::query()
            ->where('course_id', $course->id)
            ->withCount('chapters')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Topic $topic) => $this->formatNamedNode($topic, (int) $topic->chapters_count))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listChapters(Student $student, int $topicId): array
    {
        $topic = $this->enrolledTopic($student, $topicId);

        return $topic->chapters()
            ->withCount('units')
            ->get()
            ->map(fn (Chapter $chapter) => $this->formatNamedNode($chapter, (int) $chapter->units_count))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listUnits(Student $student, int $chapterId): array
    {
        $chapter = $this->enrolledChapter($student, $chapterId);

        return $chapter->units()
            ->withCount('knowledgeCards')
            ->get()
            ->map(fn (Unit $unit) => $this->formatNamedNode($unit, (int) $unit->knowledge_cards_count))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listKnowledgeCards(Student $student, int $unitId): array
    {
        $unit = $this->enrolledUnit($student, $unitId);

        return $unit->knowledgeCards()
            ->get()
            ->map(fn (KnowledgeCard $card) => [
                'id' => $card->id,
                'title' => $card->title,
                'name' => $card->title,
                'type' => $card->type ?: 'keyword',
                'content' => $card->content,
                'example' => $card->example,
                'code_example' => $card->example,
                'sort_order' => $card->sort_order,
                'created_at' => $card->created_at,
                'updated_at' => $card->updated_at,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function courseGraph(Student $student, int $courseId): array
    {
        $course = $this->enrolledCourse($student, $courseId);
        $course->load([
            'topics.chapters.units.knowledgeCards' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return [
            'id' => $course->id,
            'name' => $course->name,
            'topics' => $course->topics->map(fn (Topic $topic) => [
                'id' => $topic->id,
                'name' => $topic->name,
                'sort_order' => $topic->sort_order,
                'chapters' => $topic->chapters->map(fn (Chapter $chapter) => [
                    'id' => $chapter->id,
                    'name' => $chapter->name,
                    'title' => $chapter->name,
                    'sort_order' => $chapter->sort_order,
                    'units' => $chapter->units->map(fn (Unit $unit) => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'title' => $unit->name,
                        'sort_order' => $unit->sort_order,
                        'knowledge_cards' => $unit->knowledgeCards->map(fn (KnowledgeCard $card) => [
                            'id' => $card->id,
                            'title' => $card->title,
                            'name' => $card->title,
                            'type' => $card->type ?: 'keyword',
                            'content' => $card->content,
                            'example' => $card->example,
                            'code_example' => $card->example,
                            'sort_order' => $card->sort_order,
                        ])->values()->all(),
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function enrolledCourse(Student $student, int $courseId): Course
    {
        $course = Course::query()
            ->whereKey($courseId)
            ->whereHas('students', fn (Builder $query) => $query->where('students.id', $student->id))
            ->first();

        if ($course === null) {
            throw new ModelNotFoundException();
        }

        return $course;
    }

    private function enrolledTopic(Student $student, int $topicId): Topic
    {
        $topic = Topic::query()
            ->whereKey($topicId)
            ->whereHas('course.students', fn (Builder $query) => $query->where('students.id', $student->id))
            ->first();

        if ($topic === null) {
            throw new ModelNotFoundException();
        }

        return $topic;
    }

    private function enrolledChapter(Student $student, int $chapterId): Chapter
    {
        $chapter = Chapter::query()
            ->whereKey($chapterId)
            ->whereHas('topic.course.students', fn (Builder $query) => $query->where('students.id', $student->id))
            ->first();

        if ($chapter === null) {
            throw new ModelNotFoundException();
        }

        return $chapter;
    }

    private function enrolledUnit(Student $student, int $unitId): Unit
    {
        $unit = Unit::query()
            ->whereKey($unitId)
            ->whereHas('chapter.topic.course.students', fn (Builder $query) => $query->where('students.id', $student->id))
            ->first();

        if ($unit === null) {
            throw new ModelNotFoundException();
        }

        return $unit;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatNamedNode(\Illuminate\Database\Eloquent\Model $model, int $itemCount): array
    {
        return [
            'id' => $model->getKey(),
            'name' => $model->getAttribute('name'),
            'sort_order' => $model->getAttribute('sort_order'),
            'item_count' => $itemCount,
            'created_at' => $model->getAttribute('created_at'),
            'updated_at' => $model->getAttribute('updated_at'),
        ];
    }
}
