<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Teacher;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Excel 教材匯入：append（接在最後）／replace（重建指定章節）／overwrite（整課重建）。
 * 預覽只讀不寫；正式匯入在交易內鎖課程、比對預覽指紋、重算計畫後寫入。
 */
class MaterialImportService
{
    public function __construct(
        private readonly ExcelMaterialParser $parser,
        private readonly CourseService $courseService,
        private readonly MaterialService $materialService,
        private readonly MaterialImportPlanner $planner,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function preview(Teacher $teacher, int $courseId, string $path, ?string $mode, ?int $chapterId): array
    {
        $course = $this->courseService->ownedCourse($teacher, $courseId);
        $chapters = $this->parse($path);
        $mode = $this->resolveMode($course, $mode);
        $target = $this->resolveTarget($course, $mode, $chapterId, $chapters);

        $plan = $this->planner->plan($course, $mode, $chapters, $target);

        return [
            'mode' => $mode,
            'fingerprint' => $this->fingerprint($course),
            'summary' => $this->summary($plan),
            ...$this->previewTree($course, $plan),
            'removed' => [
                'units' => $plan['removed_unit_names'],
                'deleted_cards' => $this->cardList($plan, $plan['delete_card_ids']),
                'detached_cards' => $this->cardList($plan, $plan['detach_card_ids']),
            ],
            'affected_questions' => $plan['affected_questions'],
            'duplicate_chapter_names' => $plan['duplicate_chapter_names'],
        ];
    }

    /**
     * @return array{course: array<string, mixed>, summary: array<string, int>}
     */
    public function import(
        Teacher $teacher,
        int $courseId,
        string $path,
        ?string $mode,
        ?int $chapterId = null,
        ?string $fingerprint = null,
    ): array {
        $course = $this->courseService->ownedCourse($teacher, $courseId);
        $chapters = $this->parse($path);

        $summary = DB::transaction(function () use ($course, $chapters, $mode, $chapterId, $fingerprint): array {
            $course = Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();

            if ($fingerprint !== null && ! hash_equals($this->fingerprint($course), $fingerprint)) {
                throw new HttpException(409, '教材在預覽後已被修改，請重新預覽後再匯入');
            }

            $mode = $this->resolveMode($course, $mode);
            $target = $this->resolveTarget($course, $mode, $chapterId, $chapters);
            $plan = $this->planner->plan($course, $mode, $chapters, $target);

            $this->execute($course, $plan);

            return $this->summary($plan);
        });

        return [
            'course' => $this->materialService->courseTree($teacher, $course->id),
            'summary' => $summary,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parse(string $path): array
    {
        try {
            $parsed = $this->parser->parse($path);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'file' => [$exception->getMessage()],
            ]);
        }

        $chapters = $parsed['chapters'] ?? [];
        if ($chapters === []) {
            throw ValidationException::withMessages([
                'file' => ['沒有可匯入的教材列'],
            ]);
        }

        return $chapters;
    }

    private function resolveMode(Course $course, ?string $mode): string
    {
        if ($mode !== null) {
            return $mode;
        }

        $hasContent = $course->chapters()->exists()
            || KnowledgeCard::query()->where('course_id', $course->id)->exists();

        if ($hasContent) {
            $message = '此課程已有教材，請選擇匯入方式（附加匯入／替換章節／覆蓋匯入）';

            throw ValidationException::withMessages([
                'mode' => [$message],
                'overwrite' => [$message],
            ]);
        }

        return MaterialImportPlanner::MODE_OVERWRITE;
    }

    /**
     * @param  list<array<string, mixed>>  $chapters
     */
    private function resolveTarget(Course $course, string $mode, ?int $chapterId, array $chapters): ?Chapter
    {
        if ($mode !== MaterialImportPlanner::MODE_REPLACE) {
            return null;
        }

        $target = $chapterId === null ? null : Chapter::query()
            ->where('course_id', $course->id)
            ->whereKey($chapterId)
            ->with('units')
            ->first();

        if ($target === null) {
            throw ValidationException::withMessages([
                'chapter_id' => ['找不到要替換的章節，或章節不屬於此課程'],
            ]);
        }

        if (count($chapters) !== 1) {
            throw ValidationException::withMessages([
                'file' => ['替換章節時，Excel 只能有一個章節（目前有 '.count($chapters).' 個）'],
            ]);
        }

        return $target;
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function execute(Course $course, array $plan): void
    {
        $clearedUnitIds = $plan['delete_unit_ids'];

        if ($clearedUnitIds !== []) {
            DB::table('knowledge_card_unit')->whereIn('unit_id', $clearedUnitIds)->delete();
            KnowledgeCard::query()->whereIn('unit_id', $clearedUnitIds)->update(['unit_id' => null]);
        }

        if ($plan['delete_chapter_ids'] !== []) {
            Chapter::query()->whereIn('id', $plan['delete_chapter_ids'])->delete();
        } elseif ($clearedUnitIds !== []) {
            Unit::query()->whereIn('id', $clearedUnitIds)->delete();
        }

        if ($plan['delete_card_ids'] !== []) {
            // 題目關聯隨 knowledge_card cascade 一併拿掉；題目與作答紀錄保留
            KnowledgeCard::query()->whereIn('id', $plan['delete_card_ids'])->delete();
        }

        foreach ($plan['update_cards'] as $cardId => $payload) {
            KnowledgeCard::query()->whereKey($cardId)->update($payload);
        }

        $createdCards = [];
        $touchedCardIds = $plan['detach_card_ids'];

        foreach ($plan['chapters'] as $chapterSpec) {
            if ($chapterSpec['existing_id'] !== null) {
                $chapter = Chapter::query()->findOrFail($chapterSpec['existing_id']);
                $chapter->update(['name' => $chapterSpec['name']]);
            } else {
                $chapter = Chapter::query()->create([
                    'course_id' => $course->id,
                    'name' => $chapterSpec['name'],
                    'sort_order' => $chapterSpec['sort_order'],
                ]);
            }

            foreach ($chapterSpec['units'] as $unitSpec) {
                $unit = Unit::query()->create([
                    'chapter_id' => $chapter->id,
                    'name' => $unitSpec['name'],
                    'sort_order' => $unitSpec['sort_order'],
                    'status' => 'published',
                ]);

                foreach ($unitSpec['cards'] as $ref) {
                    if ($ref['id'] !== null) {
                        $cardId = $ref['id'];
                    } else {
                        $createdCards[$ref['key']] ??= KnowledgeCard::query()->create([
                            ...collect($plan['new_cards'][$ref['key']])->except('possible_duplicate')->all(),
                            'course_id' => $course->id,
                            'unit_id' => $unit->id,
                        ])->id;
                        $cardId = $createdCards[$ref['key']];
                    }

                    $unit->knowledgeCards()->syncWithoutDetaching([$cardId]);
                    $touchedCardIds[] = $cardId;
                }
            }
        }

        $this->repairPrimaryUnits(array_values(array_unique($touchedCardIds)));
    }

    /**
     * 主要單元被清掉的卡改指向仍掛著的第一個單元；都沒有則維持空值（題目仍可使用）。
     *
     * @param  list<int>  $cardIds
     */
    private function repairPrimaryUnits(array $cardIds): void
    {
        if ($cardIds === []) {
            return;
        }

        $firstUnits = DB::table('knowledge_card_unit')
            ->whereIn('knowledge_card_id', $cardIds)
            ->groupBy('knowledge_card_id')
            ->selectRaw('knowledge_card_id, MIN(unit_id) as unit_id')
            ->pluck('unit_id', 'knowledge_card_id');

        KnowledgeCard::query()
            ->whereIn('id', $cardIds)
            ->whereNull('unit_id')
            ->get(['id'])
            ->each(function (KnowledgeCard $card) use ($firstUnits): void {
                $unitId = $firstUnits->get($card->id);
                if ($unitId !== null) {
                    KnowledgeCard::query()->whereKey($card->id)->update(['unit_id' => $unitId]);
                }
            });
    }

    /**
     * 教材目前狀態的雜湊；預覽回傳，正式匯入比對，不一致代表中間被改過。
     */
    private function fingerprint(Course $course): string
    {
        $chapters = Chapter::query()
            ->where('course_id', $course->id)
            ->orderBy('id')
            ->get(['id', 'name', 'sort_order', 'updated_at']);

        $units = Unit::query()
            ->whereIn('chapter_id', $chapters->modelKeys())
            ->orderBy('id')
            ->get(['id', 'chapter_id', 'name', 'sort_order', 'status', 'updated_at']);

        $cards = KnowledgeCard::query()
            ->where('course_id', $course->id)
            ->orderBy('id')
            ->get(['id', 'unit_id', 'title', 'type', 'content', 'example', 'sort_order', 'updated_at'])
            ->map(fn (KnowledgeCard $card) => [
                $card->id,
                $card->unit_id,
                $card->title,
                $card->type,
                md5((string) $card->content),
                md5((string) $card->example),
                $card->sort_order,
                (string) $card->updated_at,
            ]);

        $unitLinks = DB::table('knowledge_card_unit')
            ->whereIn('unit_id', $units->modelKeys())
            ->orderBy('unit_id')
            ->orderBy('knowledge_card_id')
            ->get(['unit_id', 'knowledge_card_id']);

        $questionLinks = DB::table('question_knowledge_cards')
            ->whereIn('knowledge_card_id', $cards->pluck(0))
            ->orderBy('knowledge_card_id')
            ->orderBy('question_id')
            ->get(['knowledge_card_id', 'question_id']);

        return hash('sha256', json_encode([
            $chapters->map(fn (Chapter $chapter) => [$chapter->id, $chapter->name, $chapter->sort_order, (string) $chapter->updated_at]),
            $units->map(fn (Unit $unit) => [$unit->id, $unit->chapter_id, $unit->name, $unit->sort_order, $unit->status, (string) $unit->updated_at]),
            $cards,
            $unitLinks,
            $questionLinks,
        ]));
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array<string, int>
     */
    private function summary(array $plan): array
    {
        $units = collect($plan['chapters'])->flatMap(fn (array $chapter) => $chapter['units']);
        $refs = $units->flatMap(fn (array $unit) => $unit['cards']);
        $existingRefs = $refs->whereNotNull('id')->unique('id');

        return [
            'chapters' => collect($plan['chapters'])->whereNull('existing_id')->count(),
            'units' => $units->count(),
            'new_cards' => count($plan['new_cards']),
            'reused_cards' => $existingRefs->where('tone', 'reuse')->count(),
            'updated_cards' => $existingRefs->where('tone', 'replace')->count(),
            'possible_duplicates' => collect($plan['new_cards'])->where('possible_duplicate', true)->count(),
            'removed_units' => count($plan['removed_unit_names']),
            'deleted_cards' => count($plan['delete_card_ids']),
            'detached_cards' => count($plan['detach_card_ids']),
            'affected_questions' => count($plan['affected_questions']),
            'questions_without_cards' => collect($plan['affected_questions'])->where('remaining_cards', 0)->count(),
        ];
    }

    /**
     * 匯入後的教材樹（格式同 tree API）；新建節點用負數暫時 ID，highlights 以 chapter:/unit:/card: 為 key。
     *
     * @param  array<string, mixed>  $plan
     * @return array{course: array<string, mixed>, highlights: array<string, string>}
     */
    private function previewTree(Course $course, array $plan): array
    {
        $tempId = 0;
        $newCardIds = [];
        $highlights = [];

        $built = collect($plan['chapters'])->map(function (array $chapterSpec) use ($plan, &$tempId, &$newCardIds, &$highlights): array {
            $chapterId = $chapterSpec['existing_id'] ?? --$tempId;
            if ($chapterSpec['tone'] !== null) {
                $highlights["chapter:{$chapterId}"] = $chapterSpec['tone'];
            }

            $units = collect($chapterSpec['units'])->map(function (array $unitSpec) use ($plan, &$tempId, &$newCardIds, &$highlights): array {
                $unitId = --$tempId;
                if ($unitSpec['tone'] !== null) {
                    $highlights["unit:{$unitId}"] = $unitSpec['tone'];
                }

                $cards = collect($unitSpec['cards'])->map(function (array $ref) use ($plan, &$tempId, &$newCardIds, &$highlights): array {
                    if ($ref['id'] !== null) {
                        $card = $plan['cards']->get($ref['id']);
                        $formatted = $this->materialService->formatCard($card);
                        $formatted = array_merge($formatted, $plan['update_cards'][$card->id] ?? []);
                        $formatted['code_example'] = $formatted['example'];
                        $cardId = $card->id;
                    } else {
                        $newCardIds[$ref['key']] ??= --$tempId;
                        $cardId = $newCardIds[$ref['key']];
                        $spec = $plan['new_cards'][$ref['key']];
                        $formatted = [
                            'id' => $cardId,
                            'title' => $spec['title'],
                            'name' => $spec['title'],
                            'type' => $spec['type'],
                            'content' => $spec['content'],
                            'example' => $spec['example'],
                            'code_example' => $spec['example'],
                            'sort_order' => $spec['sort_order'],
                            'created_at' => null,
                            'updated_at' => null,
                        ];
                    }

                    $highlights["card:{$cardId}"] = $ref['tone'];

                    return $formatted;
                })->sortBy('sort_order')->values()->all();

                return [
                    'id' => $unitId,
                    'name' => $unitSpec['name'],
                    'title' => $unitSpec['name'],
                    'sort_order' => $unitSpec['sort_order'],
                    'status' => 'published',
                    'knowledge_cards' => $cards,
                ];
            })->values()->all();

            return [
                'id' => $chapterId,
                'name' => $chapterSpec['name'],
                'title' => $chapterSpec['name'],
                'sort_order' => $chapterSpec['sort_order'],
                'units' => $units,
            ];
        });

        $existing = collect($this->materialService->formatChapters(
            $plan['existing_chapters']->load(['units.knowledgeCards' => fn ($query) => $query->orderBy('sort_order')])
        ));

        $chapters = match ($plan['mode']) {
            MaterialImportPlanner::MODE_APPEND => $existing->concat($built),
            MaterialImportPlanner::MODE_REPLACE => $existing->map(
                fn (array $chapter) => $chapter['id'] === $plan['target_chapter_id'] ? $built->first() : $chapter
            ),
            default => $built,
        };

        return [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'chapters' => $chapters->sortBy('sort_order')->values()->all(),
            ],
            'highlights' => $highlights,
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  list<int>  $cardIds
     * @return list<array{id: int, title: string, questions: int}>
     */
    private function cardList(array $plan, array $cardIds): array
    {
        return collect($cardIds)
            ->map(fn (int $id) => $plan['cards']->get($id))
            ->filter()
            ->map(fn (KnowledgeCard $card) => [
                'id' => $card->id,
                'title' => $card->title,
                'questions' => $card->questions->count(),
            ])
            ->values()
            ->all();
    }
}
