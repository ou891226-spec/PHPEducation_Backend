<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\KnowledgeCard;
use App\Models\Teacher;
use App\Models\Topic;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Excel 直接寫入正式教材。有舊內容須帶 overwrite 才覆蓋。
 */
class MaterialImportService
{
    public function __construct(
        private readonly ExcelMaterialParser $parser,
        private readonly CourseService $courseService,
        private readonly MaterialService $materialService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function import(Teacher $teacher, int $courseId, string $path, string $topicName, bool $overwrite): array
    {
        $course = $this->courseService->ownedCourse($teacher, $courseId);

        try {
            $parsed = $this->parser->parse($path, $topicName);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'file' => [$exception->getMessage()],
            ]);
        }

        $topicNode = $parsed['topics'][0] ?? null;
        if ($topicNode === null) {
            throw ValidationException::withMessages([
                'file' => ['沒有可匯入的教材列'],
            ]);
        }

        return DB::transaction(function () use ($teacher, $course, $topicNode, $topicName, $overwrite) {
            $topic = Topic::query()->firstOrCreate(
                [
                    'course_id' => $course->id,
                    'name' => $topicName,
                ],
                [
                    'sort_order' => (int) Topic::query()->where('course_id', $course->id)->max('sort_order') + 1,
                ],
            );

            $hasContent = $topic->chapters()->exists()
                || KnowledgeCard::query()->where('topic_id', $topic->id)->exists();

            if ($hasContent && ! $overwrite) {
                throw ValidationException::withMessages([
                    'overwrite' => ['此主題已有教材，請確認覆蓋後再上傳'],
                ]);
            }

            if ($hasContent) {
                $this->clearTopicTree($topic);
            }

            $this->buildTopicTree($topic, $topicNode);

            return $this->materialService->topicTree($teacher, $topic->id);
        });
    }

    private function clearTopicTree(Topic $topic): void
    {
        $topic->load('chapters.units.knowledgeCards');

        $existing = KnowledgeCard::query()
            ->where('topic_id', $topic->id)
            ->get()
            ->keyBy(fn (KnowledgeCard $card) => $this->cardKey($card->title, $card->type));

        foreach ($topic->chapters as $chapter) {
            foreach ($chapter->units as $unit) {
                $unit->knowledgeCards()->detach();
            }
        }

        foreach ($existing as $card) {
            $card->update(['unit_id' => null]);
        }

        $topic->chapters()->delete();
    }

    /**
     * @param  array<string, mixed>  $topicNode
     */
    private function buildTopicTree(Topic $topic, array $topicNode): void
    {
        $reusable = KnowledgeCard::query()
            ->where('topic_id', $topic->id)
            ->get()
            ->keyBy(fn (KnowledgeCard $card) => $this->cardKey($card->title, $card->type));
        $usedIds = [];

        foreach ($topicNode['chapters'] as $chapterNode) {
            $chapter = Chapter::query()->create([
                'topic_id' => $topic->id,
                'name' => $chapterNode['name'],
                'sort_order' => $chapterNode['sort_order'] ?: 1,
            ]);

            foreach ($chapterNode['units'] as $unitNode) {
                $unit = Unit::query()->create([
                    'chapter_id' => $chapter->id,
                    'name' => $unitNode['name'],
                    'sort_order' => $unitNode['sort_order'] ?: 1,
                ]);

                foreach ($unitNode['knowledge_cards'] as $cardNode) {
                    $key = $this->cardKey($cardNode['title'], $cardNode['type']);
                    $card = $reusable->get($key);

                    if ($card === null) {
                        $card = KnowledgeCard::query()->create([
                            'topic_id' => $topic->id,
                            'unit_id' => $unit->id,
                            'title' => $cardNode['title'],
                            'type' => $cardNode['type'],
                            'content' => $cardNode['content'] !== '' ? $cardNode['content'] : $cardNode['title'],
                            'example' => $cardNode['example'],
                            'sort_order' => $cardNode['sort_order'] ?: 1,
                        ]);
                        $reusable->put($key, $card);
                    } else {
                        $card->update([
                            'unit_id' => $card->unit_id ?? $unit->id,
                            'content' => $cardNode['content'] !== '' ? $cardNode['content'] : $card->content,
                            'example' => $cardNode['example'] ?? $card->example,
                            'sort_order' => $cardNode['sort_order'] ?: $card->sort_order,
                        ]);
                    }

                    $unit->knowledgeCards()->syncWithoutDetaching([$card->id]);
                    $usedIds[$card->id] = true;
                }
            }
        }

        foreach ($reusable as $card) {
            if (isset($usedIds[$card->id])) {
                continue;
            }

            if ($card->questions()->exists()) {
                $card->update(['unit_id' => null]);
                continue;
            }

            $card->delete();
        }
    }

    private function cardKey(string $title, string $type): string
    {
        return mb_strtolower(trim($title))."\0".mb_strtolower(trim($type));
    }
}
