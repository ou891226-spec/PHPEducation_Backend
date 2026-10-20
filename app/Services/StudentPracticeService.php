<?php

namespace App\Services;

use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Student;
use App\Models\Unit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * 學生題目練習：依題目掛的知識卡分範圍，並在知識卡未完成前鎖住題目。
 * 單卡的 B11 選擇／是非題屬於即學即測，不在題目練習出現；跨卡的 B11 題照常練習。
 *
 * - card：只掛一張知識卡
 * - unit：多張卡，有共同單元
 * - chapter：多張卡，不同單元但有共同章節
 * - course：跨章節（或沒掛知識卡）的綜合練習
 */
class StudentPracticeService
{
    public const SCOPE_CARD = 'card';

    public const SCOPE_UNIT = 'unit';

    public const SCOPE_CHAPTER = 'chapter';

    public const SCOPE_COURSE = 'course';

    public function __construct(
        private readonly StudentQuestionService $questionService,
        private readonly StudentKnowledgeCardService $cardService,
    ) {}

    /**
     * 篩選條件都可省略；有給 per_page 才分頁並回 meta。
     *
     * @param  array{knowledge_card_id?: int|null, type?: string|null, keyword?: string|null, scope?: string|null, chapter_id?: int|null, unit_id?: int|null, status?: string|null, page?: int|null, per_page?: int|null}  $filters
     * @return array{questions: list<array<string, mixed>>, chapters: list<array<string, mixed>>, meta?: array<string, int>}
     */
    public function list(Student $student, int $courseId, array $filters = []): array
    {
        $questions = $this->questionService->listForStudent($student, $courseId, $filters);
        $structure = $this->structure($student, Course::query()->findOrFail($courseId));

        $items = [];
        foreach ($questions as $question) {
            if ($this->isCardQuiz($question)) {
                continue;
            }

            $practice = $this->classify($question['knowledge_cards'], $structure);

            // 題目掛到尚未發布的知識卡，學生還學不到，先不列出
            if ($practice !== null) {
                $item = [...$question, ...$practice];

                if ($this->matches($item, $filters)) {
                    $items[] = $item;
                }
            }
        }

        $result = ['questions' => $items, 'chapters' => $structure['tree']];

        $perPage = $filters['per_page'] ?? null;
        if ($perPage === null) {
            return $result;
        }

        $total = count($items);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($filters['page'] ?? 1, $lastPage);

        $result['questions'] = array_slice($items, ($page - 1) * $perPage, $perPage);
        $result['meta'] = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
        ];

        return $result;
    }

    /**
     * 範圍：選了 scope 才看章節／單元；知識卡已在查詢時篩過。
     *
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $filters
     */
    private function matches(array $item, array $filters): bool
    {
        $scope = $filters['scope'] ?? null;

        if ($scope !== null) {
            if ($item['scope'] !== $scope) {
                return false;
            }

            $chapterId = $filters['chapter_id'] ?? null;
            if ($scope !== self::SCOPE_COURSE && $chapterId !== null && ! in_array($chapterId, $item['scope_chapter_ids'], true)) {
                return false;
            }

            $unitId = $filters['unit_id'] ?? null;
            if (in_array($scope, [self::SCOPE_CARD, self::SCOPE_UNIT], true) && $unitId !== null && ! in_array($unitId, $item['scope_unit_ids'], true)) {
                return false;
            }
        }

        return match ($filters['status'] ?? 'all') {
            'answered' => $item['practice'] !== null,
            'unanswered' => $item['practice'] === null,
            default => true,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function find(Student $student, int $questionId): array
    {
        $question = $this->questionService->findForStudent($student, $questionId);

        if ($this->isCardQuiz($question)) {
            throw new ModelNotFoundException();
        }

        $structure = $this->structure($student, Course::query()->findOrFail($question['course_id']));
        $practice = $this->classify($question['knowledge_cards'], $structure);

        if ($practice === null) {
            throw new ModelNotFoundException();
        }

        return [...$question, ...$practice];
    }

    /**
     * 只掛一張知識卡的 B11 選擇／是非題只在該卡的即學即測出現，不列入題目練習。
     *
     * @param  array<string, mixed>  $question
     */
    private function isCardQuiz(array $question): bool
    {
        return count($question['knowledge_cards']) === 1
            && $question['bloom_id'] === StudentKnowledgeCardService::QUIZ_BLOOM_ID
            && in_array($question['type'], StudentKnowledgeCardService::QUIZ_TYPES, true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function submit(Student $student, int $questionId, array $data): array
    {
        $question = $this->find($student, $questionId);

        if ($question['locked']) {
            $titles = array_column($question['locked_cards'], 'title');

            throw ValidationException::withMessages([
                'question' => ['請先完成知識卡：'.implode('、', $titles)],
            ]);
        }

        return $this->questionService->submit($student, $questionId, $data);
    }

    /**
     * @param  list<array{id: int, title: string}>  $cards
     * @param  array{card_units: array<int, list<int>>, unit_chapter: array<int, int>, completed: array<int, bool>}  $structure
     * @return array<string, mixed>|null
     */
    private function classify(array $cards, array $structure): ?array
    {
        if ($cards === []) {
            return [
                'scope' => self::SCOPE_COURSE,
                'scope_unit_ids' => [],
                'scope_chapter_ids' => [],
                'locked' => false,
                'locked_cards' => [],
            ];
        }

        $unitSets = [];
        $chapterSets = [];
        foreach ($cards as $card) {
            $unitIds = $structure['card_units'][$card['id']] ?? [];
            if ($unitIds === []) {
                return null;
            }

            $unitSets[] = $unitIds;
            $chapterSets[] = array_values(array_unique(
                array_map(fn (int $unitId) => $structure['unit_chapter'][$unitId], $unitIds),
            ));
        }

        $commonUnits = array_values(array_intersect(...$unitSets));
        $commonChapters = array_values(array_intersect(...$chapterSets));

        $scope = match (true) {
            count($cards) === 1 => self::SCOPE_CARD,
            $commonUnits !== [] => self::SCOPE_UNIT,
            $commonChapters !== [] => self::SCOPE_CHAPTER,
            default => self::SCOPE_COURSE,
        };

        $lockedCards = array_values(array_map(
            fn (array $card) => ['id' => $card['id'], 'title' => $card['title']],
            array_filter($cards, fn (array $card) => ! isset($structure['completed'][$card['id']])),
        ));

        return [
            'scope' => $scope,
            'scope_unit_ids' => $commonUnits,
            'scope_chapter_ids' => $commonChapters,
            'locked' => $lockedCards !== [],
            'locked_cards' => $lockedCards,
        ];
    }

    /**
     * 已發布單元的章節／單元／知識卡結構，給範圍判斷與前端下拉選單使用。
     *
     * @return array{tree: list<array<string, mixed>>, card_units: array<int, list<int>>, unit_chapter: array<int, int>, completed: array<int, bool>}
     */
    private function structure(Student $student, Course $course): array
    {
        $course->load([
            'chapters.units' => fn ($query) => $query->where('status', 'published')->orderBy('sort_order'),
            'chapters.units.knowledgeCards' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        $completed = array_fill_keys($this->cardService->completedCardIds($student, $course), true);
        $cardUnits = [];
        $unitChapter = [];

        $tree = $course->chapters
            ->map(function ($chapter) use ($completed, &$cardUnits, &$unitChapter) {
                return [
                    'id' => $chapter->id,
                    'name' => $chapter->name,
                    'units' => $chapter->units
                        ->map(function (Unit $unit) use ($chapter, $completed, &$cardUnits, &$unitChapter) {
                            $unitChapter[$unit->id] = $chapter->id;

                            return [
                                'id' => $unit->id,
                                'name' => $unit->name,
                                'knowledge_cards' => $unit->knowledgeCards
                                    ->map(function (KnowledgeCard $card) use ($unit, $completed, &$cardUnits) {
                                        $cardUnits[$card->id][] = $unit->id;

                                        return [
                                            'id' => $card->id,
                                            'title' => $card->title,
                                            'completed' => isset($completed[$card->id]),
                                        ];
                                    })
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'tree' => $tree,
            'card_units' => $cardUnits,
            'unit_chapter' => $unitChapter,
            'completed' => $completed,
        ];
    }
}
