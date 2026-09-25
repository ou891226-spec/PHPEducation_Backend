<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Support\MaterialText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 依匯入模式算出「要寫入什麼」，只讀資料庫；預覽直接回傳，正式匯入在交易內重算後執行。
 *
 * - append：新章節接在最後，不改不刪任何既有資料；內容完全相同的卡沿用。
 * - replace：只重建指定章節；同標題＋類型的卡沿用並蓋掉內容；
 *   移出的卡若有題目或其他單元使用只拆關聯，否則刪除。
 * - overwrite：整門課以 Excel 為準；同標題＋類型的卡沿用並蓋掉內容，其餘卡一律刪除（含題目關聯）。
 */
class MaterialImportPlanner
{
    public const MODE_APPEND = 'append';

    public const MODE_REPLACE = 'replace';

    public const MODE_OVERWRITE = 'overwrite';

    public const MODES = [self::MODE_APPEND, self::MODE_REPLACE, self::MODE_OVERWRITE];

    /**
     * @param  list<array<string, mixed>>  $parsedChapters
     * @return array<string, mixed>
     */
    public function plan(Course $course, string $mode, array $parsedChapters, ?Chapter $target = null): array
    {
        $existingChapters = Chapter::query()
            ->where('course_id', $course->id)
            ->with('units')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $courseCards = $this->courseCards($course, $existingChapters);

        $state = [
            'mode' => $mode,
            'cards' => $courseCards->keyBy('id'),
            'by_title' => $this->firstBy($courseCards, fn (KnowledgeCard $card) => MaterialText::titleKey($card->title, $card->type)),
            'by_content' => $this->firstBy($courseCards, fn (KnowledgeCard $card) => MaterialText::contentKey($card->title, $card->type, $card->content)),
            'chapter_by_title' => collect(),
            'file_refs' => [],
            'new_cards' => [],
            'update_cards' => [],
            'used_ids' => [],
        ];

        $chapterSpecs = [];
        $deleteChapterIds = [];
        $deleteUnitIds = [];
        $duplicateChapterNames = [];

        if ($mode === self::MODE_APPEND) {
            $baseOrder = (int) $existingChapters->max('sort_order');
            $existingNames = $existingChapters->map(fn (Chapter $chapter) => MaterialText::normalize($chapter->name))->all();

            foreach (array_values($parsedChapters) as $index => $chapterNode) {
                if (in_array(MaterialText::normalize($chapterNode['name']), $existingNames, true)) {
                    $duplicateChapterNames[] = $chapterNode['name'];
                }
                $chapterSpecs[] = $this->chapterSpec($state, $chapterNode, null, $baseOrder + $index + 1, 'new', fn () => 'new');
            }
        } elseif ($mode === self::MODE_REPLACE) {
            $targetUnitIds = $target->units->modelKeys();
            $chapterCards = $courseCards->filter(fn (KnowledgeCard $card) => $this->cardInUnits($card, $targetUnitIds));
            $state['chapter_by_title'] = $this->firstBy($chapterCards, fn (KnowledgeCard $card) => MaterialText::titleKey($card->title, $card->type));

            $existingUnitNames = $target->units->map(fn ($unit) => MaterialText::normalize($unit->name))->all();
            $chapterSpecs[] = $this->chapterSpec(
                $state,
                $parsedChapters[0],
                $target->id,
                (int) $target->sort_order,
                'replace',
                fn (string $unitName) => in_array(MaterialText::normalize($unitName), $existingUnitNames, true) ? null : 'new',
            );
            $deleteUnitIds = $targetUnitIds;
        } else {
            $existingNames = [];
            foreach ($existingChapters as $chapter) {
                $chapterKey = MaterialText::normalize($chapter->name);
                $existingNames[$chapterKey] = $chapter->units
                    ->map(fn ($unit) => MaterialText::normalize($unit->name))
                    ->all();
            }

            foreach ($parsedChapters as $chapterNode) {
                $chapterKey = MaterialText::normalize($chapterNode['name']);
                $knownUnits = $existingNames[$chapterKey] ?? null;
                $chapterSpecs[] = $this->chapterSpec(
                    $state,
                    $chapterNode,
                    null,
                    (int) ($chapterNode['sort_order'] ?: 1),
                    $knownUnits === null ? 'new' : null,
                    fn (string $unitName) => $knownUnits !== null && in_array(MaterialText::normalize($unitName), $knownUnits, true) ? null : 'new',
                );
            }

            $deleteChapterIds = $existingChapters->modelKeys();
            $deleteUnitIds = $existingChapters->flatMap(fn (Chapter $chapter) => $chapter->units->modelKeys())->all();
        }

        [$deleteCardIds, $detachCardIds] = $this->removals($mode, $courseCards, $state['used_ids'], $deleteUnitIds);

        $affectedQuestions = $this->affectedQuestions($courseCards, $deleteCardIds);

        return [
            'mode' => $mode,
            'target_chapter_id' => $target?->id,
            'chapters' => $chapterSpecs,
            'delete_chapter_ids' => $deleteChapterIds,
            'delete_unit_ids' => $deleteUnitIds,
            'new_cards' => $state['new_cards'],
            'update_cards' => $state['update_cards'],
            'delete_card_ids' => $deleteCardIds,
            'detach_card_ids' => $detachCardIds,
            'duplicate_chapter_names' => $duplicateChapterNames,
            'affected_questions' => $affectedQuestions,
            'removed_unit_names' => $mode === self::MODE_REPLACE
                ? $this->removedUnitNames($target, $parsedChapters[0])
                : [],
            'cards' => $state['cards'],
            'existing_chapters' => $existingChapters,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $chapterNode
     * @param  callable(string): ?string  $unitTone
     * @return array<string, mixed>
     */
    private function chapterSpec(array &$state, array $chapterNode, ?int $existingId, int $sortOrder, ?string $tone, callable $unitTone): array
    {
        $units = [];
        foreach ($chapterNode['units'] as $unitNode) {
            $cards = [];
            foreach ($unitNode['knowledge_cards'] as $cardNode) {
                $cards[] = $this->resolveCard($state, $cardNode);
            }

            $units[] = [
                'name' => $unitNode['name'],
                'sort_order' => (int) ($unitNode['sort_order'] ?: 1),
                'tone' => $tone === 'new' ? 'new' : $unitTone($unitNode['name']),
                'cards' => $cards,
            ];
        }

        return [
            'existing_id' => $existingId,
            'name' => $chapterNode['name'],
            'sort_order' => $sortOrder,
            'tone' => $tone,
            'units' => $units,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $cardNode
     * @return array{id: ?int, key: ?string, tone: string}
     */
    private function resolveCard(array &$state, array $cardNode): array
    {
        $titleKey = MaterialText::titleKey($cardNode['title'], $cardNode['type']);

        if (isset($state['file_refs'][$titleKey])) {
            $ref = $state['file_refs'][$titleKey];
            $this->mergeRepeatedCard($state, $ref, $cardNode);

            return $ref;
        }

        $mode = $state['mode'];
        $ref = null;

        if ($mode !== self::MODE_APPEND) {
            $scope = $mode === self::MODE_REPLACE ? $state['chapter_by_title'] : $state['by_title'];
            $existing = $scope->get($titleKey);

            if ($existing !== null) {
                $payload = $this->updatedPayload([
                    'content' => $existing->content,
                    'example' => $existing->example,
                    'sort_order' => $existing->sort_order,
                ], $cardNode);
                $state['update_cards'][$existing->id] = $payload;
                $changed = MaterialText::normalize($payload['content']) !== MaterialText::normalize($existing->content)
                    || (string) $payload['example'] !== (string) $existing->example;
                $ref = ['id' => $existing->id, 'key' => null, 'tone' => $changed ? 'replace' : 'reuse'];
            }
        }

        if ($ref === null && $mode !== self::MODE_OVERWRITE) {
            $existing = $state['by_content']->get(
                MaterialText::contentKey($cardNode['title'], $cardNode['type'], $cardNode['content'])
            );
            if ($existing !== null) {
                $ref = ['id' => $existing->id, 'key' => null, 'tone' => 'reuse'];
            }
        }

        if ($ref === null) {
            $duplicate = $mode !== self::MODE_OVERWRITE && $state['by_title']->has($titleKey);
            $state['new_cards'][$titleKey] = [
                'title' => $cardNode['title'],
                'type' => $cardNode['type'],
                'content' => $cardNode['content'] !== '' ? $cardNode['content'] : $cardNode['title'],
                'example' => $cardNode['example'],
                'sort_order' => (int) ($cardNode['sort_order'] ?: 1),
                'possible_duplicate' => $duplicate,
            ];
            $ref = ['id' => null, 'key' => $titleKey, 'tone' => $duplicate ? 'warn' : 'new'];
        }

        if ($ref['id'] !== null) {
            $state['used_ids'][$ref['id']] = true;
        }
        $state['file_refs'][$titleKey] = $ref;

        return $ref;
    }

    /**
     * 同一檔案重複出現的卡指向同一張；後出現的內容覆蓋前面（與舊版整課匯入一致）。
     *
     * @param  array<string, mixed>  $state
     * @param  array{id: ?int, key: ?string, tone: string}  $ref
     * @param  array<string, mixed>  $cardNode
     */
    private function mergeRepeatedCard(array &$state, array $ref, array $cardNode): void
    {
        if ($ref['key'] !== null) {
            $state['new_cards'][$ref['key']] = array_merge(
                $state['new_cards'][$ref['key']],
                $this->updatedPayload($state['new_cards'][$ref['key']], $cardNode),
            );

            return;
        }

        if (isset($state['update_cards'][$ref['id']])) {
            $state['update_cards'][$ref['id']] = $this->updatedPayload($state['update_cards'][$ref['id']], $cardNode);
        }
    }

    /**
     * @param  array{content: ?string, example: ?string, sort_order: ?int}  $current
     * @param  array<string, mixed>  $cardNode
     * @return array{content: ?string, example: ?string, sort_order: ?int}
     */
    private function updatedPayload(array $current, array $cardNode): array
    {
        return [
            'content' => $cardNode['content'] !== '' ? $cardNode['content'] : $current['content'],
            'example' => $cardNode['example'] ?? $current['example'],
            'sort_order' => (int) ($cardNode['sort_order'] ?: $current['sort_order']),
        ];
    }

    /**
     * @param  Collection<int, KnowledgeCard>  $courseCards
     * @param  array<int, bool>  $usedIds
     * @param  list<int>  $clearedUnitIds
     * @return array{0: list<int>, 1: list<int>}
     */
    private function removals(string $mode, Collection $courseCards, array $usedIds, array $clearedUnitIds): array
    {
        if ($mode === self::MODE_APPEND) {
            return [[], []];
        }

        $delete = [];
        $detach = [];

        foreach ($courseCards as $card) {
            if (isset($usedIds[$card->id])) {
                continue;
            }

            if ($mode === self::MODE_OVERWRITE) {
                $delete[] = $card->id;

                continue;
            }

            if (! $this->cardInUnits($card, $clearedUnitIds)) {
                continue;
            }

            $usedElsewhere = $card->units->pluck('id')->diff($clearedUnitIds)->isNotEmpty()
                || ($card->unit_id !== null && ! in_array((int) $card->unit_id, $clearedUnitIds, true));

            if ($card->questions->isNotEmpty() || $usedElsewhere) {
                $detach[] = $card->id;
            } else {
                $delete[] = $card->id;
            }
        }

        return [$delete, $detach];
    }

    /**
     * @param  Collection<int, KnowledgeCard>  $courseCards
     * @param  list<int>  $deleteCardIds
     * @return list<array{id: int, title: string, remaining_cards: int}>
     */
    private function affectedQuestions(Collection $courseCards, array $deleteCardIds): array
    {
        if ($deleteCardIds === []) {
            return [];
        }

        $questions = $courseCards
            ->whereIn('id', $deleteCardIds)
            ->flatMap(fn (KnowledgeCard $card) => $card->questions)
            ->unique('id');

        if ($questions->isEmpty()) {
            return [];
        }

        $links = DB::table('question_knowledge_cards')
            ->whereIn('question_id', $questions->pluck('id'))
            ->get(['question_id', 'knowledge_card_id'])
            ->groupBy('question_id');

        return $questions
            ->sortBy('id')
            ->map(fn ($question) => [
                'id' => $question->id,
                'title' => $question->title,
                'remaining_cards' => ($links->get($question->id) ?? collect())
                    ->pluck('knowledge_card_id')
                    ->diff($deleteCardIds)
                    ->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $fileChapter
     * @return list<string>
     */
    private function removedUnitNames(Chapter $target, array $fileChapter): array
    {
        $fileNames = collect($fileChapter['units'])->map(fn (array $unit) => MaterialText::normalize($unit['name']))->all();

        return $target->units
            ->reject(fn ($unit) => in_array(MaterialText::normalize($unit->name), $fileNames, true))
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Chapter>  $chapters
     * @return Collection<int, KnowledgeCard>
     */
    private function courseCards(Course $course, Collection $chapters): Collection
    {
        $unitIds = $chapters->flatMap(fn (Chapter $chapter) => $chapter->units->modelKeys())->all();

        return KnowledgeCard::query()
            ->with(['units:id,chapter_id', 'questions:id,title'])
            ->where(function (Builder $query) use ($course, $unitIds): void {
                $query->where('course_id', $course->id)
                    ->orWhereIn('unit_id', $unitIds)
                    ->orWhereHas('units', fn (Builder $units) => $units->whereIn('units.id', $unitIds));
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<int>  $unitIds
     */
    private function cardInUnits(KnowledgeCard $card, array $unitIds): bool
    {
        return in_array((int) $card->unit_id, $unitIds, true)
            || $card->units->pluck('id')->intersect($unitIds)->isNotEmpty();
    }

    /**
     * @param  Collection<int, KnowledgeCard>  $cards
     * @return \Illuminate\Support\Collection<string, KnowledgeCard>
     */
    private function firstBy(Collection $cards, callable $key): \Illuminate\Support\Collection
    {
        $map = [];
        foreach ($cards as $card) {
            $map[$key($card)] ??= $card;
        }

        return collect($map);
    }
}
