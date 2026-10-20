<?php

namespace App\Services;

use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\KnowledgeCardCompletion;
use App\Models\Question;
use App\Models\QuestionRecord;
use App\Models\Student;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 學生知識卡學習：即學即測與完成狀態。
 *
 * - 即學即測題目：只掛此知識卡的 B11（記憶／事實）選擇題與是非題，每位學生固定抽 5 題；跨卡題留在題目練習
 * - 抽到的題目都作答過 → 知識卡完成（不論對錯）；答錯可重答
 * - 沒有 B11 題目：按「我已讀完」即完成
 */
class StudentKnowledgeCardService
{
    public const QUIZ_TYPES = [Question::TYPE_CHOICE, Question::TYPE_TRUE_FALSE];

    public const QUIZ_BLOOM_ID = 'B11';

    public const QUIZ_SIZE = 5;

    public function __construct(
        private readonly StudentQuestionService $questionService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function quiz(Student $student, int $courseId, int $cardId): array
    {
        $course = $this->enrolledCourse($student, $courseId);
        $card = $this->publishedCard($course, $cardId);
        $questions = $this->quizQuestions($student, $course, $card);
        $statuses = $this->questionStatuses($student, $questions);
        $completion = $this->syncCompletion($student, $course, $card, $questions, $statuses);
        $unit = $this->publishedUnitOf($course, $card);

        return [
            'card' => [
                'id' => $card->id,
                'title' => $card->title,
                'type' => $card->type ?: 'keyword',
                'content' => $card->content,
                'example' => $card->example,
                'chapter' => $unit?->chapter === null ? null : [
                    'id' => $unit->chapter->id,
                    'name' => $unit->chapter->name,
                ],
                'unit' => $unit === null ? null : [
                    'id' => $unit->id,
                    'name' => $unit->name,
                ],
            ],
            'completed' => $completion !== null,
            'completed_at' => $completion?->completed_at,
            'completion_method' => $completion?->method,
            'has_quiz' => $questions->isNotEmpty(),
            'question_count' => $questions->count(),
            'questions' => $questions
                ->map(fn (Question $question) => [
                    ...$this->questionService->formatQuestionForStudent($question),
                    ...$statuses[$question->id],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * 即學即測：作答單題（批改沿用題目練習），回傳結果與知識卡完成狀態。
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function submitQuiz(Student $student, int $courseId, int $cardId, int $questionId, array $data): array
    {
        $course = $this->enrolledCourse($student, $courseId);
        $card = $this->publishedCard($course, $cardId);
        $questions = $this->quizQuestions($student, $course, $card);

        if (! $questions->contains('id', $questionId)) {
            throw ValidationException::withMessages([
                'question_id' => ['題目不屬於此知識卡的小試身手。'],
            ]);
        }

        $result = $this->questionService->submit($student, $questionId, $data, QuestionRecord::SOURCE_QUIZ);

        $statuses = $this->questionStatuses($student, $questions);
        $completion = $this->syncCompletion($student, $course, $card, $questions, $statuses);

        return [
            ...$result,
            'question_status' => $statuses[$questionId],
            'correct_count' => collect($statuses)->where('correct', true)->count(),
            'total' => $questions->count(),
            'completed' => $completion !== null,
            'completed_at' => $completion?->completed_at,
        ];
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return array<int, array{answered: bool, correct: bool, last_status: string|null}>
     */
    private function questionStatuses(Student $student, Collection $questions): array
    {
        $records = QuestionRecord::query()
            ->where('student_id', $student->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->orderBy('id')
            ->get(['question_id', 'system_status'])
            ->groupBy('question_id');

        return $questions
            ->mapWithKeys(function (Question $question) use ($records) {
                $items = $records->get($question->id, collect());

                return [$question->id => [
                    'answered' => $items->isNotEmpty(),
                    'correct' => $items->contains('system_status', QuestionRecord::STATUS_CORRECT),
                    'last_status' => $items->last()?->system_status,
                ]];
            })
            ->all();
    }

    /**
     * 抽到的題目都作答過就寫入完成紀錄，不論對錯。
     *
     * @param  Collection<int, Question>  $questions
     * @param  array<int, array{answered: bool}>  $statuses
     */
    private function syncCompletion(
        Student $student,
        Course $course,
        KnowledgeCard $card,
        Collection $questions,
        array $statuses,
    ): ?KnowledgeCardCompletion {
        $completion = $this->completion($student, $course, $card);

        // 讀完即完成只適用沒有題目的卡；老師之後掛了題目就要重新作答
        if ($completion?->method === KnowledgeCardCompletion::METHOD_READ && $questions->isNotEmpty()) {
            $completion = null;
        }

        if ($completion !== null || $questions->isEmpty()) {
            return $completion;
        }

        $allAnswered = collect($statuses)->every(fn (array $status) => $status['answered']);

        return $allAnswered
            ? $this->markCompleted($student, $course, $card, KnowledgeCardCompletion::METHOD_QUIZ)
            : null;
    }

    /**
     * 沒有即學即測題目的知識卡：學生讀完即算完成。
     *
     * @return array<string, mixed>
     */
    public function markRead(Student $student, int $courseId, int $cardId): array
    {
        $course = $this->enrolledCourse($student, $courseId);
        $card = $this->publishedCard($course, $cardId);

        if ($this->quizQuestions($student, $course, $card)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'card' => ['此知識卡需完成小試身手才算學會。'],
            ]);
        }

        $completion = $this->completion($student, $course, $card)
            ?? $this->markCompleted($student, $course, $card, KnowledgeCardCompletion::METHOD_READ);

        return [
            'completed' => true,
            'completed_at' => $completion->completed_at,
        ];
    }

    /**
     * 學習分析：每張知識卡的作答進度與狀態，以及上次做到哪裡。
     *
     * 狀態：completed 已完成 / retry 有答錯待重做 / in_progress 作答中 / not_started 未開始
     *
     * @return array<string, mixed>
     */
    public function progress(Student $student, int $courseId): array
    {
        $course = $this->enrolledCourse($student, $courseId);
        $course->load([
            'chapters.units' => fn ($query) => $query->where('status', 'published')->orderBy('sort_order'),
            'chapters.units.knowledgeCards' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        $completedIds = $this->completionMethods($student, $course);

        $cardQuestionIds = $this->cardQuizQuestionIds($student, $course);

        $records = QuestionRecord::query()
            ->with('question:id,title')
            ->where('student_id', $student->id)
            ->whereHas('question', fn (Builder $query) => $query->where('course_id', $course->id))
            ->orderBy('id')
            ->get(['id', 'question_id', 'system_status', 'created_at']);

        $questionStats = $records->groupBy('question_id')->map(fn ($items) => [
            'correct' => $items->contains('system_status', QuestionRecord::STATUS_CORRECT),
            'last_at' => $items->last()->created_at,
        ]);

        $summary = ['completed' => 0, 'retry' => 0, 'in_progress' => 0, 'not_started' => 0];
        $cardsInOrder = [];

        $chapters = $course->chapters
            ->map(function ($chapter) use ($student, $course, $completedIds, $cardQuestionIds, $questionStats, &$summary, &$cardsInOrder) {
                $units = $chapter->units->map(function (Unit $unit) use ($student, $course, $completedIds, $cardQuestionIds, $questionStats, &$summary, &$cardsInOrder) {
                    $cards = $unit->knowledgeCards->map(function (KnowledgeCard $card) use ($student, $course, $completedIds, $cardQuestionIds, $questionStats, &$summary, &$cardsInOrder) {
                        $questionIds = $cardQuestionIds->get($card->id, collect());
                        $stats = $questionIds->map(fn ($id) => $questionStats->get($id))->filter();
                        $answered = $stats->count();
                        $correct = $stats->where('correct', true)->count();
                        $total = $questionIds->count();
                        $completed = $completedIds->has($card->id)
                            && $this->countsAsCompleted($completedIds->get($card->id), $total > 0);

                        if (! $completed && $total > 0 && $answered === $total) {
                            $this->markCompleted($student, $course, $card, KnowledgeCardCompletion::METHOD_QUIZ);
                            $completed = true;
                        }

                        $status = match (true) {
                            $completed => 'completed',
                            $answered === 0 => 'not_started',
                            $answered > $correct => 'retry',
                            default => 'in_progress',
                        };
                        $summary[$status]++;

                        $item = [
                            'id' => $card->id,
                            'title' => $card->title,
                            'has_quiz' => $total > 0,
                            'question_total' => $total,
                            'answered_count' => $answered,
                            'correct_count' => $correct,
                            'status' => $status,
                            'last_answered_at' => $stats->max('last_at'),
                        ];
                        $cardsInOrder[] = $item;

                        return $item;
                    })->values();

                    return [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'total' => $cards->count(),
                        'completed' => $cards->where('status', 'completed')->count(),
                        'knowledge_cards' => $cards->all(),
                    ];
                })->values();

                return [
                    'id' => $chapter->id,
                    'name' => $chapter->name,
                    'total' => $units->sum('total'),
                    'completed' => $units->sum('completed'),
                    'units' => $units->all(),
                ];
            })
            ->filter(fn (array $chapter) => count($chapter['units']) > 0)
            ->values()
            ->all();

        return [
            'course_id' => $course->id,
            'total' => count($cardsInOrder),
            'completed' => $summary['completed'],
            'summary' => $summary,
            'last_activity' => $this->lastActivity($records, $cardQuestionIds, $cardsInOrder),
            'chapters' => $chapters,
        ];
    }

    /**
     * 最近一次作答的題目與它所屬（第一張未完成）的知識卡，讓學生知道上次做到哪。
     *
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, int>>  $cardQuestionIds
     * @param  list<array<string, mixed>>  $cardsInOrder
     * @return array<string, mixed>|null
     */
    private function lastActivity(Collection $records, $cardQuestionIds, array $cardsInOrder): ?array
    {
        $last = $records->last();
        if ($last === null) {
            return null;
        }

        $cards = collect($cardsInOrder)
            ->filter(fn (array $card) => $cardQuestionIds->get($card['id'], collect())->contains($last->question_id));
        $card = $cards->firstWhere('status', '!=', 'completed') ?? $cards->first();

        return [
            'question_id' => $last->question_id,
            'question_title' => $last->question?->title,
            'system_status' => $last->system_status,
            'answered_at' => $last->created_at,
            'knowledge_card' => $card === null ? null : [
                'id' => $card['id'],
                'title' => $card['title'],
                'status' => $card['status'],
            ],
        ];
    }

    /**
     * 各知識卡的完成狀態與是否有即學即測，供教材樹附加。
     *
     * @return array<int, array{completed: bool, has_quiz: bool}>
     */
    public function cardStatusMap(Student $student, Course $course): array
    {
        $quizCardIds = $this->cardQuizQuestionIds($student, $course);

        $completedIds = $this->completionMethods($student, $course)
            ->filter(fn (string $method, int $cardId) => $this->countsAsCompleted($method, $quizCardIds->has($cardId)));

        $cardIds = $completedIds->keys()->merge($quizCardIds->keys())->unique();

        return $cardIds
            ->mapWithKeys(fn ($id) => [(int) $id => [
                'completed' => $completedIds->has($id),
                'has_quiz' => $quizCardIds->has($id),
            ]])
            ->all();
    }

    /**
     * 已完成的知識卡：有完成紀錄，或抽到的即學即測題目都作答過。
     *
     * @return list<int>
     */
    public function completedCardIds(Student $student, Course $course): array
    {
        $cardQuestionIds = $this->cardQuizQuestionIds($student, $course);

        $answeredIds = QuestionRecord::query()
            ->where('student_id', $student->id)
            ->whereIn('question_id', $cardQuestionIds->flatten()->unique()->values())
            ->distinct()
            ->pluck('question_id')
            ->flip();

        $completed = [];

        foreach ($this->completionMethods($student, $course) as $cardId => $method) {
            if ($this->countsAsCompleted($method, $cardQuestionIds->has($cardId))) {
                $completed[(int) $cardId] = true;
            }
        }

        foreach ($cardQuestionIds as $cardId => $questionIds) {
            if ($questionIds->every(fn ($id) => $answeredIds->has($id))) {
                $completed[(int) $cardId] = true;
            }
        }

        return array_keys($completed);
    }

    /**
     * @return Collection<int, Question>
     */
    private function quizQuestions(Student $student, Course $course, KnowledgeCard $card): Collection
    {
        $questions = Question::query()
            ->with(['options', 'subAnswers', 'knowledgeCards:id,title,content,example'])
            ->where('course_id', $course->id)
            ->whereIn('type', self::QUIZ_TYPES)
            ->where('bloom_id', self::QUIZ_BLOOM_ID)
            ->whereHas('knowledgeCards', fn (Builder $query) => $query->whereKey($card->id))
            ->has('knowledgeCards', '=', 1)
            ->orderBy('id')
            ->get();

        $picked = $this->pickQuizIds($student, $card->id, $questions->pluck('id')->all());

        return $questions->whereIn('id', $picked)->values();
    }

    /**
     * 每位學生每張卡固定抽 QUIZ_SIZE 題：依學生、知識卡、題目雜湊排序，重新整理不會換題，
     * 不同學生抽到的題目不同。
     *
     * @param  list<int>  $questionIds
     * @return list<int>
     */
    private function pickQuizIds(Student $student, int $cardId, array $questionIds): array
    {
        $ranks = [];
        foreach ($questionIds as $id) {
            $ranks[$id] = md5("{$student->id}-{$cardId}-{$id}");
        }
        asort($ranks);

        $picked = array_slice(array_keys($ranks), 0, self::QUIZ_SIZE);
        sort($picked);

        return $picked;
    }

    /**
     * 每張知識卡抽給此學生的即學即測題目 id。
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, int>>
     */
    private function cardQuizQuestionIds(Student $student, Course $course): \Illuminate\Support\Collection
    {
        return DB::table('question_knowledge_cards')
            ->join('questions', 'questions.id', '=', 'question_knowledge_cards.question_id')
            ->where('questions.course_id', $course->id)
            ->whereIn('questions.type', self::QUIZ_TYPES)
            ->where('questions.bloom_id', self::QUIZ_BLOOM_ID)
            ->whereIn('question_knowledge_cards.question_id', DB::table('question_knowledge_cards')
                ->select('question_id')
                ->groupBy('question_id')
                ->havingRaw('COUNT(*) = 1'))
            ->get(['question_knowledge_cards.knowledge_card_id', 'question_knowledge_cards.question_id'])
            ->groupBy('knowledge_card_id')
            ->map(fn ($rows, $cardId) => collect($this->pickQuizIds(
                $student,
                (int) $cardId,
                $rows->pluck('question_id')->map(fn ($id) => (int) $id)->unique()->values()->all(),
            )));
    }

    private function completion(Student $student, Course $course, KnowledgeCard $card): ?KnowledgeCardCompletion
    {
        return KnowledgeCardCompletion::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('knowledge_card_id', $card->id)
            ->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, string> knowledge_card_id => method
     */
    private function completionMethods(Student $student, Course $course): \Illuminate\Support\Collection
    {
        return KnowledgeCardCompletion::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->pluck('method', 'knowledge_card_id');
    }

    private function countsAsCompleted(string $method, bool $hasQuiz): bool
    {
        return $method === KnowledgeCardCompletion::METHOD_QUIZ || ! $hasQuiz;
    }

    private function markCompleted(Student $student, Course $course, KnowledgeCard $card, string $method): KnowledgeCardCompletion
    {
        return KnowledgeCardCompletion::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'knowledge_card_id' => $card->id,
            ],
            [
                'method' => $method,
                'completed_at' => now(),
            ],
        );
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

    private function publishedCard(Course $course, int $cardId): KnowledgeCard
    {
        $card = KnowledgeCard::query()
            ->whereKey($cardId)
            ->whereHas('units', fn (Builder $query) => $query
                ->where('status', 'published')
                ->whereHas('chapter', fn (Builder $chapter) => $chapter->where('course_id', $course->id)))
            ->first();

        if ($card === null) {
            throw new ModelNotFoundException();
        }

        return $card;
    }

    private function publishedUnitOf(Course $course, KnowledgeCard $card): ?Unit
    {
        return $card->units()
            ->with('chapter')
            ->where('status', 'published')
            ->whereHas('chapter', fn (Builder $chapter) => $chapter->where('course_id', $course->id))
            ->first();
    }
}
