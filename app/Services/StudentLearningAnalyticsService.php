<?php

namespace App\Services;

use App\Models\Bloom;
use App\Models\QuestionRecord;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * 學生學習分析儀表板：Bloom 能力雷達、知識卡進度、題目練習狀況（只用學生自己的作答資料）。
 *
 * 雷達與題目練習只算正式題目：即學即測可重答，會讓答對率失真。
 */
class StudentLearningAnalyticsService
{
    public function __construct(
        private readonly StudentKnowledgeCardService $knowledgeCardService,
        private readonly StudentPracticeService $practiceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function analytics(Student $student, int $courseId): array
    {
        $progress = $this->knowledgeCardService->progress($student, $courseId);
        $questions = collect($this->practiceService->list($student, $courseId)['questions']);

        $total = $progress['total'];
        $percent = $total > 0 ? round($progress['completed'] / $total * 100, 1) : 0.0;

        return [
            'progress_percent' => $percent,
            'bloom_radar' => $this->bloomRadar($questions),
            'activities' => [
                'knowledge_cards' => ['done' => $progress['completed'], 'total' => $progress['total']],
                'practice' => [
                    ...$this->practiceStats($questions),
                    'scopes' => collect([
                        StudentPracticeService::SCOPE_CARD,
                        StudentPracticeService::SCOPE_UNIT,
                        StudentPracticeService::SCOPE_CHAPTER,
                        StudentPracticeService::SCOPE_COURSE,
                    ])
                        ->map(fn (string $scope) => [
                            'scope' => $scope,
                            ...$this->practiceStats($questions->where('scope', $scope)),
                        ])
                        ->all(),
                ],
                'chapters' => collect($progress['chapters'])
                    ->map(fn (array $chapter) => [
                        'id' => $chapter['id'],
                        'name' => $chapter['name'],
                        'done' => $chapter['completed'],
                        'total' => $chapter['total'],
                    ])
                    ->all(),
            ],
        ];
    }

    /**
     * Bloom 四層級答對率（待批改不計）。
     *
     * @param  Collection<int, array<string, mixed>>  $questions
     * @return list<array<string, mixed>>
     */
    private function bloomRadar(Collection $questions): array
    {
        return collect(Bloom::LEVELS)->map(function (string $title, int $level) use ($questions) {
            $stats = $this->practiceStats($questions->filter(fn (array $question) => Bloom::levelOf($question['bloom_id']) === $level));

            return [
                'level' => $level,
                'title' => $title,
                'question_count' => $stats['total'],
                'answered_count' => $stats['graded'],
                'correct_count' => $stats['correct'],
                'rate' => $stats['rate'],
            ];
        })->values()->all();
    }

    /**
     * 答對率 = 答對 / 已批改，待批改的實作題不計。
     *
     * @param  Collection<int, array<string, mixed>>  $questions
     * @return array{total: int, answered: int, graded: int, correct: int, rate: float|null}
     */
    private function practiceStats(Collection $questions): array
    {
        $statuses = $questions
            ->pluck('practice')
            ->filter()
            ->pluck('status');

        $graded = $statuses->reject(fn (?string $status) => $status === null || $status === QuestionRecord::STATUS_PENDING);
        $correct = $graded->filter(fn (string $status) => $status === QuestionRecord::STATUS_CORRECT)->count();

        return [
            'total' => $questions->count(),
            'answered' => $statuses->count(),
            'graded' => $graded->count(),
            'correct' => $correct,
            'rate' => $graded->isEmpty() ? null : round($correct / $graded->count() * 100, 1),
        ];
    }
}
