<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Teacher;
use App\Models\Topic;
use App\Models\Unit;
use App\Support\KnowledgeConcept;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 正式教材鑽層 CRUD（topics / chapters / units / knowledge_cards）。
 */
class MaterialService
{
    /**
     * @return array<string, mixed>
     */
    public function courseTree(Teacher $teacher, int $courseId): array
    {
        $course = $this->ownedCourse($teacher, $courseId);
        $course->load([
            'topics.chapters.units.knowledgeCards' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return [
            'id' => $course->id,
            'name' => $course->name,
            'topics' => $course->topics->map(fn (Topic $topic) => $this->formatTreeTopic($topic))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function topicTree(Teacher $teacher, int $topicId): array
    {
        $topic = $this->ownedTopic($teacher, $topicId);
        $topic->load([
            'chapters.units.knowledgeCards' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return $this->formatTreeTopic($topic);
    }

    public function listTopics(Teacher $teacher, int $courseId): array
    {
        $course = $this->ownedCourse($teacher, $courseId);

        return Topic::query()
            ->where('course_id', $course->id)
            ->withCount('chapters')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
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
        $topic = $this->ownedTopic($teacher, $topicId);

        DB::transaction(function () use ($topic): void {
            $topic->load('chapters.units.knowledgeCards');
            foreach ($topic->chapters as $chapter) {
                $this->detachOrDeleteCardsInChapter($chapter);
            }
            $topic->delete();
        });
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
        $chapter = $this->ownedChapter($teacher, $chapterId);

        DB::transaction(function () use ($chapter): void {
            $this->detachOrDeleteCardsInChapter($chapter);
            $chapter->delete();
        });
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
        $unit = $this->ownedUnit($teacher, $unitId);

        DB::transaction(function () use ($unit): void {
            $this->detachOrDeleteCardsInUnit($unit);
            $unit->delete();
        });
    }

    public function listKnowledgeCards(Teacher $teacher, int $unitId): array
    {
        $unit = $this->ownedUnit($teacher, $unitId);

        return $unit->knowledgeCards()
            ->get()
            ->map(fn (KnowledgeCard $card) => $this->formatCard($card))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listKnowledgeCardsForCourse(Teacher $teacher, int $courseId): array
    {
        $course = $this->ownedCourse($teacher, $courseId);

        $cardRelations = ['unit.chapter.topic', 'unit.chapter.units', 'units.chapter.topic', 'units.chapter.units', 'topic'];

        $fromTree = KnowledgeCard::query()
            ->with($cardRelations)
            ->where(function (Builder $query) use ($course): void {
                $query->whereHas(
                    'unit.chapter.topic',
                    fn (Builder $topics) => $topics->where('course_id', $course->id)
                )->orWhereHas(
                    'units.chapter.topic',
                    fn (Builder $topics) => $topics->where('course_id', $course->id)
                )->orWhereHas(
                    'topic',
                    fn (Builder $topics) => $topics->where('course_id', $course->id)
                );
            })
            ->orderBy('id')
            ->get();

        $fromQuestions = KnowledgeCard::query()
            ->with($cardRelations)
            ->whereHas(
                'questions',
                fn (Builder $questions) => $questions->where('course_id', $course->id)
            )
            ->whereNotIn('id', $fromTree->modelKeys())
            ->orderBy('id')
            ->get();

        $best = [];
        foreach ($fromTree->concat($fromQuestions) as $card) {
            $title = KnowledgeConcept::displayTitle($card);
            if ($title === null) {
                continue;
            }

            $unit = $card->primaryUnit();
            $topicId = $unit?->chapter?->topic?->id ?? $card->topic_id;
            $key = ($topicId ?? 'none')."\0".$title;
            $item = [
                'id' => $card->id,
                'title' => $title,
                'example' => $card->example,
                'unit_name' => $unit?->name,
                'chapter_name' => $unit?->chapter?->name,
                'topic_name' => $unit?->chapter?->topic?->name ?? $card->topic?->name,
                'topic_id' => $topicId,
                'practice' => KnowledgeConcept::isPracticeSection((string) $unit?->name),
                'content_len' => mb_strlen((string) $card->content),
            ];

            if (! isset($best[$key])) {
                $best[$key] = $item;

                continue;
            }

            $newScore = $this->pickerCardScore($item);
            $oldScore = $this->pickerCardScore($best[$key]);
            if ($newScore > $oldScore || ($newScore === $oldScore && $item['id'] < $best[$key]['id'])) {
                $best[$key] = $item;
            }
        }

        return collect($best)
            ->sortBy([
                ['topic_id', 'asc'],
                ['title', 'asc'],
            ])
            ->map(fn (array $item) => [
                'id' => $item['id'],
                'title' => $item['title'],
                'example' => $item['example'],
                'unit_name' => $item['unit_name'],
                'chapter_name' => $item['chapter_name'],
                'topic_name' => $item['topic_name'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{practice: bool, example: mixed, content_len: int}  $item
     */
    private function pickerCardScore(array $item): int
    {
        return ($item['practice'] ? 0 : 8)
            + ($item['example'] ? 4 : 0)
            + min(3, intdiv($item['content_len'], 40));
    }

    public function createKnowledgeCard(Teacher $teacher, int $unitId, array $data): array
    {
        $unit = $this->ownedUnit($teacher, $unitId);
        $unit->loadMissing('chapter');

        $card = KnowledgeCard::query()->create([
            'unit_id' => $unit->id,
            'topic_id' => $unit->chapter->topic_id,
            'title' => $data['title'],
            'type' => $data['type'] ?? 'keyword',
            'content' => $data['content'],
            'example' => $data['example'] ?? null,
            'sort_order' => $data['sort_order'] ?? $this->nextSortOrder(KnowledgeCard::query()->where('unit_id', $unit->id)),
        ]);
        $card->units()->syncWithoutDetaching([$unit->id]);

        return $this->formatCard($card);
    }

    public function updateKnowledgeCard(Teacher $teacher, int $cardId, array $data): array
    {
        $card = $this->ownedCard($teacher, $cardId);

        $payload = [
            'title' => $data['title'],
            'type' => $data['type'] ?? $card->type ?? 'keyword',
            'content' => $data['content'],
            'example' => $data['example'] ?? null,
        ];

        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $payload['sort_order'] = $data['sort_order'];
        }

        $card->update($payload);

        return $this->formatCard($card->fresh());
    }

    public function deleteKnowledgeCard(Teacher $teacher, int $cardId): void
    {
        $card = $this->ownedCard($teacher, $cardId);
        if ($card->questions()->exists()) {
            throw ValidationException::withMessages([
                'knowledge_card' => ['此知識卡已有題目使用，無法刪除'],
            ]);
        }

        $card->delete();
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
        $ownsCourse = fn (Builder $query) => $query->where('teacher_id', $teacher->id);

        $card = KnowledgeCard::query()
            ->whereKey($cardId)
            ->where(function (Builder $query) use ($ownsCourse): void {
                $query->whereHas('unit.chapter.topic.course', $ownsCourse)
                    ->orWhereHas('units.chapter.topic.course', $ownsCourse)
                    ->orWhereHas('topic.course', $ownsCourse)
                    ->orWhereHas('questions.course', $ownsCourse);
            })
            ->first();

        if ($card === null) {
            throw new ModelNotFoundException();
        }

        return $card;
    }

    private function detachOrDeleteCardsInChapter(Chapter $chapter): void
    {
        $chapter->loadMissing('units.knowledgeCards');
        foreach ($chapter->units as $unit) {
            $this->detachOrDeleteCardsInUnit($unit);
        }
    }

    private function detachOrDeleteCardsInUnit(Unit $unit): void
    {
        $unit->loadMissing('knowledgeCards');
        foreach ($unit->knowledgeCards as $card) {
            $unit->knowledgeCards()->detach($card->id);
            $remaining = $card->units()->count();

            if ($remaining > 0) {
                if ((int) $card->unit_id === (int) $unit->id) {
                    $card->update(['unit_id' => $card->units()->orderBy('units.id')->value('units.id')]);
                }
                continue;
            }

            if ($card->questions()->exists()) {
                $card->update(['unit_id' => null]);
                continue;
            }

            $card->delete();
        }
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
            'created_at' => $model->getAttribute('created_at'),
            'updated_at' => $model->getAttribute('updated_at'),
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
            'name' => $card->title,
            'type' => $card->type ?: 'keyword',
            'content' => $card->content,
            'example' => $card->example,
            'code_example' => $card->example,
            'sort_order' => $card->sort_order,
            'created_at' => $card->created_at,
            'updated_at' => $card->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTreeTopic(Topic $topic): array
    {
        return [
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
                    'knowledge_cards' => $unit->knowledgeCards->map(fn (KnowledgeCard $card) => $this->formatCard($card))->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
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
