<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Teacher;
use App\Models\Topic;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * 正式教材鑽層 CRUD（topics / chapters / units / knowledge_cards）。
 */
class MaterialService
{
    public function listTopics(Teacher $teacher, int $courseId): array
    {
        $course = $this->ownedCourse($teacher, $courseId);

        return $course->topics()
            ->withCount('chapters')
            ->get()
            ->map(fn (Topic $topic) => $this->formatNamedNode($topic, (int) $topic->chapters_count))
            ->all();
    }

    public function createTopic(Teacher $teacher, int $courseId, array $data): array
    {
        $course = $this->ownedCourse($teacher, $courseId);
        $this->assertUniqueTopicName($course->id, $data['name']);

        $topic = Topic::query()->create([
            'course_id' => $course->id,
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? $this->nextSortOrder(Topic::query()->where('course_id', $course->id)),
        ]);

        return $this->formatNamedNode($topic, 0);
    }

    public function updateTopic(Teacher $teacher, int $topicId, array $data): array
    {
        $topic = $this->ownedTopic($teacher, $topicId);
        $this->assertUniqueTopicName($topic->course_id, $data['name'], $topic->id);
        $this->updateNamedNode($topic, $data);
        $topic = $topic->fresh()->loadCount('chapters');

        return $this->formatNamedNode($topic, (int) $topic->chapters_count);
    }

    public function deleteTopic(Teacher $teacher, int $topicId): void
    {
        $this->ownedTopic($teacher, $topicId)->delete();
    }

    public function listChapters(Teacher $teacher, int $topicId): array
    {
        $topic = $this->ownedTopic($teacher, $topicId);

        return $topic->chapters()
            ->withCount('units')
            ->get()
            ->map(fn (Chapter $chapter) => $this->formatNamedNode($chapter, (int) $chapter->units_count))
            ->all();
    }

    public function createChapter(Teacher $teacher, int $topicId, array $data): array
    {
        $topic = $this->ownedTopic($teacher, $topicId);

        $chapter = Chapter::query()->create([
            'topic_id' => $topic->id,
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? $this->nextSortOrder(Chapter::query()->where('topic_id', $topic->id)),
        ]);

        return $this->formatNamedNode($chapter, 0);
    }

    public function updateChapter(Teacher $teacher, int $chapterId, array $data): array
    {
        $chapter = $this->ownedChapter($teacher, $chapterId);
        $this->updateNamedNode($chapter, $data);
        $chapter = $chapter->fresh()->loadCount('units');

        return $this->formatNamedNode($chapter, (int) $chapter->units_count);
    }

    public function deleteChapter(Teacher $teacher, int $chapterId): void
    {
        $this->ownedChapter($teacher, $chapterId)->delete();
    }

    public function listUnits(Teacher $teacher, int $chapterId): array
    {
        $chapter = $this->ownedChapter($teacher, $chapterId);

        return $chapter->units()
            ->withCount('knowledgeCards')
            ->get()
            ->map(fn (Unit $unit) => $this->formatNamedNode($unit, (int) $unit->knowledge_cards_count))
            ->all();
    }

    public function createUnit(Teacher $teacher, int $chapterId, array $data): array
    {
        $chapter = $this->ownedChapter($teacher, $chapterId);

        $unit = Unit::query()->create([
            'chapter_id' => $chapter->id,
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? $this->nextSortOrder(Unit::query()->where('chapter_id', $chapter->id)),
        ]);

        return $this->formatNamedNode($unit, 0);
    }

    public function updateUnit(Teacher $teacher, int $unitId, array $data): array
    {
        $unit = $this->ownedUnit($teacher, $unitId);
        $this->updateNamedNode($unit, $data);
        $unit = $unit->fresh()->loadCount('knowledgeCards');

        return $this->formatNamedNode($unit, (int) $unit->knowledge_cards_count);
    }

    public function deleteUnit(Teacher $teacher, int $unitId): void
    {
        $this->ownedUnit($teacher, $unitId)->delete();
    }

    public function listKnowledgeCards(Teacher $teacher, int $unitId): array
    {
        $unit = $this->ownedUnit($teacher, $unitId);

        return $unit->knowledgeCards()
            ->get()
            ->map(fn (KnowledgeCard $card) => $this->formatCard($card))
            ->all();
    }

    public function createKnowledgeCard(Teacher $teacher, int $unitId, array $data): array
    {
        $unit = $this->ownedUnit($teacher, $unitId);

        $card = KnowledgeCard::query()->create([
            'unit_id' => $unit->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'sort_order' => $data['sort_order'] ?? $this->nextSortOrder(KnowledgeCard::query()->where('unit_id', $unit->id)),
        ]);

        return $this->formatCard($card);
    }

    public function updateKnowledgeCard(Teacher $teacher, int $cardId, array $data): array
    {
        $card = $this->ownedCard($teacher, $cardId);

        $payload = [
            'title' => $data['title'],
            'content' => $data['content'],
        ];

        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $payload['sort_order'] = $data['sort_order'];
        }

        $card->update($payload);

        return $this->formatCard($card->fresh());
    }

    public function deleteKnowledgeCard(Teacher $teacher, int $cardId): void
    {
        $this->ownedCard($teacher, $cardId)->delete();
    }

    private function ownedCourse(Teacher $teacher, int $courseId): Course
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

    private function ownedTopic(Teacher $teacher, int $topicId): Topic
    {
        $topic = Topic::query()
            ->whereKey($topicId)
            ->whereHas('course', fn (Builder $query) => $query->where('teacher_id', $teacher->id))
            ->first();

        if ($topic === null) {
            throw new ModelNotFoundException();
        }

        return $topic;
    }

    private function ownedChapter(Teacher $teacher, int $chapterId): Chapter
    {
        $chapter = Chapter::query()
            ->whereKey($chapterId)
            ->whereHas('topic.course', fn (Builder $query) => $query->where('teacher_id', $teacher->id))
            ->first();

        if ($chapter === null) {
            throw new ModelNotFoundException();
        }

        return $chapter;
    }

    private function ownedUnit(Teacher $teacher, int $unitId): Unit
    {
        $unit = Unit::query()
            ->whereKey($unitId)
            ->whereHas('chapter.topic.course', fn (Builder $query) => $query->where('teacher_id', $teacher->id))
            ->first();

        if ($unit === null) {
            throw new ModelNotFoundException();
        }

        return $unit;
    }

    private function ownedCard(Teacher $teacher, int $cardId): KnowledgeCard
    {
        $card = KnowledgeCard::query()
            ->whereKey($cardId)
            ->whereHas('unit.chapter.topic.course', fn (Builder $query) => $query->where('teacher_id', $teacher->id))
            ->first();

        if ($card === null) {
            throw new ModelNotFoundException();
        }

        return $card;
    }

    private function nextSortOrder(Builder $query): int
    {
        return (int) $query->max('sort_order') + 1;
    }

    private function updateNamedNode(Model $model, array $data): void
    {
        $payload = [
            'name' => $data['name'],
        ];

        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $payload['sort_order'] = $data['sort_order'];
        }

        $model->update($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatNamedNode(Model $model, int $itemCount): array
    {
        return [
            'id' => $model->getKey(),
            'name' => $model->getAttribute('name'),
            'sort_order' => $model->getAttribute('sort_order'),
            'item_count' => $itemCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCard(KnowledgeCard $card): array
    {
        return [
            'id' => $card->id,
            'title' => $card->title,
            'content' => $card->content,
            'sort_order' => $card->sort_order,
        ];
    }

    private function assertUniqueTopicName(int $courseId, string $name, ?int $ignoreTopicId = null): void
    {
        $name = trim($name);
        $query = Topic::query()
            ->where('course_id', $courseId)
            ->where('name', $name);

        if ($ignoreTopicId !== null) {
            $query->whereKeyNot($ignoreTopicId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => ['教材名稱與現有教材重複（'.$name.'），請修改後再試'],
            ]);
        }
    }
}
