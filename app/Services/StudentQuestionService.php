<?php

namespace App\Services;

use App\Jobs\CodingGradingJob;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionRecord;
use App\Models\QuestionSubAnswer;
use App\Models\Student;
use App\Support\AnswerText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentQuestionService
{
    /**
     * 題目列表只回摘要；題目內容、選項、知識卡內容等進作答頁再由 findForStudent 取得。
     *
     * @param  array{knowledge_card_id?: int|null, type?: string|null, keyword?: string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function listForStudent(Student $student, int $courseId, array $filters = []): array
    {
        $this->enrolledCourseId($student, $courseId);

        $query = Question::query()
            ->where('course_id', $courseId)
            ->select(['id', 'course_id', 'title', 'type', 'bloom_id'])
            ->with('knowledgeCards:id,title')
            ->orderBy('id');

        if (($filters['knowledge_card_id'] ?? null) !== null) {
            $query->whereHas('knowledgeCards', fn (Builder $cards) => $cards->whereKey($filters['knowledge_card_id']));
        }

        if (($filters['type'] ?? null) !== null) {
            $query->where('type', $filters['type']);
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where('title', 'like', '%'.addcslashes($keyword, '%_\\').'%');
        }

        $questions = $query->get();
        $practices = $this->practiceRecords($student, $questions->pluck('id')->all());

        return $questions
            ->map(fn (Question $question) => [
                'id' => $question->id,
                'course_id' => $question->course_id,
                'title' => $question->title,
                'type' => $question->type,
                'bloom_id' => $question->bloom_id,
                'knowledge_card_ids' => $question->knowledgeCards->pluck('id')->values()->all(),
                'knowledge_cards' => $question->knowledgeCards
                    ->map(fn ($card) => ['id' => $card->id, 'title' => $card->title])
                    ->values()
                    ->all(),
                'practice' => $practices[$question->id] ?? null,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function findForStudent(Student $student, int $questionId): array
    {
        $question = $this->enrolledQuestion($student, $questionId);
        $practice = $this->practiceRecords($student, [$question->id])[$question->id] ?? null;

        if ($practice !== null) {
            $record = QuestionRecord::query()->with('subs')->findOrFail($practice['record_id']);
            $practice['review'] = $this->review($question, $record);
        }

        return [
            ...$this->formatQuestionForStudent($question),
            'practice' => $practice,
        ];
    }

    /**
     * 題目練習作答後的檢討：我的答案、正確答案與說明。
     * 實作題要老師批改完才給參考答案。
     *
     * @return array{my_answer: string|null, correct_answer: string|null, subs: list<array<string, mixed>>, explanation: string|null}
     */
    private function review(Question $question, QuestionRecord $record): array
    {
        if ($question->usesOptions()) {
            $chosen = $question->options->firstWhere('id', (int) $record->result);
            $answers = $question->options->where('is_answer', true);

            return [
                'my_answer' => $chosen?->title,
                'correct_answer' => $answers->pluck('title')->implode('、') ?: null,
                'subs' => [],
                'explanation' => $answers->first()?->description,
            ];
        }

        if ($question->type === Question::TYPE_CODING) {
            $graded = $record->teacher_status !== QuestionRecord::STATUS_PENDING;

            return [
                'my_answer' => $record->result,
                'correct_answer' => $graded ? $question->reference_answer : null,
                'subs' => [],
                'explanation' => null,
            ];
        }

        $submitted = $record->subs->keyBy('sub_id');

        return [
            'my_answer' => null,
            'correct_answer' => null,
            'subs' => $question->subAnswers
                ->map(fn (QuestionSubAnswer $key) => [
                    'sub_id' => $key->sub_id,
                    'my_answer' => $submitted->get($key->sub_id)?->answer,
                    'correct_answer' => $key->answer,
                    'is_right' => (bool) $submitted->get($key->sub_id)?->is_right,
                    'description' => $key->description,
                ])
                ->values()
                ->all(),
            'explanation' => $question->subAnswers->first()?->description,
        ];
    }

    /**
     * 題目練習每題只能作答一次；即學即測的紀錄不算在內。
     *
     * @param  list<int>  $questionIds
     * @return array<int, array{record_id: int, status: string|null, answered_at: string|null}>
     */
    private function practiceRecords(Student $student, array $questionIds): array
    {
        return QuestionRecord::query()
            ->where('student_id', $student->id)
            ->where('source', QuestionRecord::SOURCE_PRACTICE)
            ->whereIn('question_id', $questionIds)
            ->orderBy('id')
            ->get(['id', 'question_id', 'teacher_status', 'system_status', 'created_at'])
            ->keyBy('question_id')
            ->map(fn (QuestionRecord $record) => [
                'record_id' => $record->id,
                'status' => $record->teacher_status ?? $record->system_status,
                'answered_at' => $record->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function submit(
        Student $student,
        int $questionId,
        array $data,
        string $source = QuestionRecord::SOURCE_PRACTICE,
    ): array {
        $question = $this->enrolledQuestion($student, $questionId);
        $isPractice = $source === QuestionRecord::SOURCE_PRACTICE;

        if ($isPractice && $this->practiceRecords($student, [$question->id]) !== []) {
            throw ValidationException::withMessages([
                'question' => ['此題已作答過，每題只能回答一次。'],
            ]);
        }

        $response = match ($question->type) {
            Question::TYPE_CHOICE, Question::TYPE_TRUE_FALSE => $this->submitChoice($student, $question, $data, $source),
            Question::TYPE_FILL, Question::TYPE_DEBUG, Question::TYPE_INTERPRET => $this->submitSubAnswers($student, $question, $data, $source),
            Question::TYPE_CODING => $this->submitCoding($student, $question, $data, $source),
            default => throw ValidationException::withMessages([
                'type' => ['不支援的題型。'],
            ]),
        };

        // 即學即測可重試，不能先給答案
        if ($isPractice) {
            $record = QuestionRecord::query()->with('subs')->findOrFail($response['record']['id']);
            $response['review'] = $this->review($question, $record);
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function submitChoice(Student $student, Question $question, array $data, string $source): array
    {
        $optionId = $data['option_id'] ?? null;
        if ($optionId === null) {
            throw ValidationException::withMessages([
                'option_id' => ['選擇／是非題請傳送 option_id。'],
            ]);
        }

        $option = QuestionOption::query()
            ->where('question_id', $question->id)
            ->whereKey($optionId)
            ->first();

        if ($option === null) {
            throw ValidationException::withMessages([
                'option_id' => ['選項不屬於此題。'],
            ]);
        }

        $correct = (bool) $option->is_answer;
        $record = $this->storeRecord(
            $student,
            $question,
            (string) $option->id,
            $correct ? QuestionRecord::STATUS_CORRECT : QuestionRecord::STATUS_WRONG,
            $option->solo ? (int) $option->solo : null,
            $source,
        );

        $correctOption = $question->options->firstWhere('is_answer', true);

        return [
            'message' => $correct ? '正確' : '錯誤',
            'record' => $this->formatRecord($record),
            'system_status' => $record->system_status,
            'explanation' => $correctOption?->description,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function submitSubAnswers(Student $student, Question $question, array $data, string $source): array
    {
        $submitted = $this->submittedSubAnswers($data);
        if ($submitted === []) {
            throw ValidationException::withMessages([
                'answers' => ['請傳送 answers，或 code_line／answer。'],
            ]);
        }

        $keys = $question->subAnswers;
        if ($keys->isEmpty()) {
            throw ValidationException::withMessages([
                'question_id' => ['此題尚未設定子題答案。'],
            ]);
        }

        $record = DB::transaction(function () use ($student, $question, $submitted, $keys, $source): QuestionRecord {
            $correctCount = 0;
            $rows = [];

            foreach ($keys as $key) {
                $studentAnswer = $submitted[$key->sub_id] ?? '';
                $isRight = AnswerText::same($studentAnswer, (string) $key->answer);
                if ($isRight) {
                    $correctCount++;
                }

                $rows[] = [
                    'sub_id' => $key->sub_id,
                    'answer' => $studentAnswer,
                    'is_right' => $isRight,
                    'solo' => $isRight ? (int) $key->solo : QuestionSubAnswer::SOLO_WRONG,
                ];
            }

            $total = $keys->count();
            $allCorrect = $correctCount === $total;
            $parentSolo = match (true) {
                $question->type === Question::TYPE_DEBUG => $this->debugSolo($submitted, $keys),
                $correctCount === 0 => QuestionRecord::SOLO_WRONG,
                $allCorrect => QuestionRecord::SOLO_ALL_CORRECT,
                default => QuestionRecord::SOLO_PARTIAL,
            };

            $record = $this->storeRecord(
                $student,
                $question,
                json_encode([
                    'correct' => $correctCount,
                    'total' => $total,
                    'answers' => $submitted,
                ], JSON_UNESCAPED_UNICODE),
                $allCorrect ? QuestionRecord::STATUS_CORRECT : QuestionRecord::STATUS_WRONG,
                $parentSolo,
                $source,
            );
            $record->subs()->createMany($rows);

            return $record->load('subs');
        });

        return [
            'message' => $record->system_status === QuestionRecord::STATUS_CORRECT ? '正確' : '錯誤',
            'record' => $this->formatRecord($record),
            'system_status' => $record->system_status,
            'description' => $keys->first()?->description,
        ];
    }

    /**
     * 除錯題整題 SOLO：行數、答案分開算對幾個。
     * 全對 4；行數對 > 1/2 且答案對 > 1/2 為 3；行數對 > 1/2 且答案對 > 1/3 為 2；其他 1。
     *
     * @param  array<int, string>  $submitted  行數 => 答案
     * @param  Collection<int, QuestionSubAnswer>  $keys
     */
    private function debugSolo(array $submitted, Collection $keys): int
    {
        $total = $keys->count();
        $lineRight = $keys->filter(fn (QuestionSubAnswer $key) => array_key_exists($key->sub_id, $submitted))->count();

        // 答案不看行數：每個學生答案只能對應一個標準答案
        $remaining = array_values($submitted);
        $answerRight = 0;
        foreach ($keys as $key) {
            foreach ($remaining as $index => $answer) {
                if (AnswerText::same($answer, (string) $key->answer)) {
                    $answerRight++;
                    unset($remaining[$index]);
                    break;
                }
            }
        }

        $exactRight = $keys->filter(
            fn (QuestionSubAnswer $key) => AnswerText::same($submitted[$key->sub_id] ?? '', (string) $key->answer)
        )->count();

        return match (true) {
            $exactRight === $total => QuestionRecord::SOLO_DEBUG_ALL_CORRECT,
            $lineRight * 2 > $total && $answerRight * 2 > $total => QuestionRecord::SOLO_ALL_CORRECT,
            $lineRight * 2 > $total && $answerRight * 3 > $total => QuestionRecord::SOLO_PARTIAL,
            default => QuestionRecord::SOLO_WRONG,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function submittedSubAnswers(array $data): array
    {
        $submitted = [];

        if (isset($data['answers']) && is_array($data['answers'])) {
            foreach ($data['answers'] as $subId => $answer) {
                if (is_array($answer)) {
                    $subId = $answer['sub_id'] ?? null;
                    $answer = $answer['answer'] ?? null;
                }
                if ($subId === null || $answer === null || $answer === '') {
                    continue;
                }
                $submitted[(int) $subId] = (string) $answer;
            }
        }

        $hasLine = array_key_exists('code_line', $data) && $data['code_line'] !== null && $data['code_line'] !== '';
        $hasAnswer = array_key_exists('answer', $data) && $data['answer'] !== null && $data['answer'] !== '';
        if ($hasLine || $hasAnswer) {
            $subId = $hasLine ? (int) $data['code_line'] : 1;
            $submitted[$subId] = $hasAnswer ? (string) $data['answer'] : '';
        }

        return $submitted;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function submitCoding(Student $student, Question $question, array $data, string $source): array
    {
        $code = $data['code'] ?? null;
        if (! is_string($code) || trim($code) === '') {
            throw ValidationException::withMessages([
                'code' => ['實作題請傳送 code。'],
            ]);
        }

        $record = $this->storeRecord(
            $student,
            $question,
            $code,
            QuestionRecord::STATUS_PENDING,
            source: $source,
        );

        // $this->gradeCodingByAi($question, $record);

        return [
            'message' => '已提交',
            'record' => $this->formatRecord($record),
            'system_status' => QuestionRecord::STATUS_PENDING,
        ];
    }

    /**
     * 實作題 AI 批改：派發至背景佇列非同步評分
     */
    private function gradeCodingByAi(Question $question, QuestionRecord $record): void
    {
        CodingGradingJob::dispatch($record);
    }

    private function storeRecord(
        Student $student,
        Question $question,
        string $result,
        string $systemStatus,
        ?int $solo = null,
        string $source = QuestionRecord::SOURCE_PRACTICE,
    ): QuestionRecord {
        return QuestionRecord::query()->create([
            'student_id' => $student->id,
            'question_id' => $question->id,
            'source' => $source,
            'result' => $result,
            'system_status' => $systemStatus,

            // 實作題等系統 pending 才要教師覆核；其餘系統已批改則同步寫入
            'teacher_status' => $systemStatus === QuestionRecord::STATUS_PENDING
                ? QuestionRecord::STATUS_PENDING
                : $systemStatus,

            'solo' => $solo ?? match ($systemStatus) {
                QuestionRecord::STATUS_CORRECT => QuestionRecord::SOLO_CORRECT,
                QuestionRecord::STATUS_WRONG => QuestionRecord::SOLO_WRONG,
                default => null,
            },
        ]);
    }

    private function enrolledCourseId(Student $student, int $courseId): int
    {
        if (! $student->courses()->where('courses.id', $courseId)->exists()) {
            throw new ModelNotFoundException();
        }

        return $courseId;
    }

    /**
     * 學生：列出自己在該課的作答紀錄（可選 question_id 篩選）
     *
     * @return list<array<string, mixed>>
     */
    public function listRecordsForStudent(Student $student, int $courseId, ?int $questionId = null): array
    {
        $this->enrolledCourseId($student, $courseId);

        return QuestionRecord::query()
            ->with(['question', 'subs', 'aiFeedback'])
            ->where('student_id', $student->id)
            ->whereHas('question', fn ($query) => $query->where('course_id', $courseId))
            ->when(
                $questionId !== null,
                fn ($query) => $query->where('question_id', $questionId),
            )
            ->orderByDesc('id')
            ->get()
            ->map(fn (QuestionRecord $record) => $this->formatHistoryRecord($record))
            ->all();
    }

    /**
     * 學生：查看自己的單筆作答紀錄
     *
     * @return array<string, mixed>
     */
    public function findRecordForStudent(Student $student, int $recordId): array
    {
        $record = QuestionRecord::query()
            ->with(['question', 'subs', 'aiFeedback'])
            ->whereKey($recordId)
            ->where('student_id', $student->id)
            ->first();

        if ($record === null) {
            throw new ModelNotFoundException();
        }

        $this->enrolledCourseId($student, (int) $record->question?->course_id);

        return $this->formatHistoryRecord($record);
    }

    private function enrolledQuestion(Student $student, int $questionId): Question
    {
        $question = Question::query()
            ->with(['options', 'subAnswers', 'knowledgeCards:id,title,content,example'])
            ->whereKey($questionId)
            ->first();

        if ($question === null) {
            throw new ModelNotFoundException();
        }

        $this->enrolledCourseId($student, (int) $question->course_id);

        return $question;
    }

    /**
     * 知識卡 example 是教材範例。老師勾 show_example 才給學生看。
     *
     * @return list<string>
     */
    private function examplesForStudent(Question $question): array
    {
        if (! $question->show_example) {
            return [];
        }

        return $question->knowledgeCards
            ->pluck('example')
            ->filter(fn ($example) => is_string($example) && $example !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatQuestionForStudent(Question $question): array
    {
        $payload = [
            'id' => $question->id,
            'course_id' => $question->course_id,
            'title' => $question->title,
            'type' => $question->type,
            'question_content' => $question->question_content,
            'bloom_id' => $question->bloom_id,
            'description' => $question->description,
            'knowledge_card_ids' => $question->knowledgeCards->pluck('id')->values()->all(),
            'examples' => $this->examplesForStudent($question),
            'knowledge_cards' => $question->knowledgeCards
                ->map(fn ($card) => [
                    'id' => $card->id,
                    'title' => $card->title,
                    'content' => $card->content,
                    'example' => $question->show_example ? $card->example : null,
                ])
                ->values()
                ->all(),
        ];

        if ($question->type === Question::TYPE_CODING) {
            $payload['starter_code'] = $question->starter_code;
        }

        if ($question->usesOptions()) {
            $payload['options'] = $question->options
                ->map(fn (QuestionOption $option) => [
                    'id' => $option->id,
                    'title' => $option->title,
                ])
                ->values()
                ->all();
        }

        if ($question->type === Question::TYPE_DEBUG) {
            $payload['debug_error_count'] = $question->subAnswers->count();
        } elseif ($question->usesSubAnswers()) {
            $payload['sub_ids'] = $question->subAnswers->pluck('sub_id')->values()->all();
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRecord(QuestionRecord $record): array
    {
        return [
            'id' => $record->id,
            'student_id' => $record->student_id,
            'question_id' => $record->question_id,
            'result' => $this->formatStoredResult($record->result),
            'system_status' => $record->system_status,
            'teacher_status' => $record->teacher_status,
            'subs' => $record->subs
                ->map(fn ($sub) => [
                    'id' => $sub->id,
                    'sub_id' => $sub->sub_id,
                    'answer' => $sub->answer,
                    'is_right' => (bool) $sub->is_right,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * 學生歷史作答（不含老師專用欄位：expected_output / reference_answer）
     *
     * @return array<string, mixed>
     */
    private function formatHistoryRecord(QuestionRecord $record): array
    {
        $needsTeacherReview = $record->question?->type === Question::TYPE_CODING;

        $aiFeedback = $record->aiFeedback;
        $feedbackContent = null;
        if ($aiFeedback !== null) {
            $decoded = json_decode($aiFeedback->feedback_content, true);
            $feedbackContent = json_last_error() === JSON_ERROR_NONE ? $decoded : $aiFeedback->feedback_content;
        }

        return [
            'id' => $record->id,
            'question_id' => $record->question_id,
            'question_title' => $record->question?->title,
            'question_type' => $record->question?->type,
            'course_id' => $record->question?->course_id,
            'result' => $this->formatStoredResult($record->result),
            'solo' => $record->solo,
            'bloom_id' => $record->bloom_id,
            'question_bloom_id' => $record->question?->bloom_id,
            'system_status' => $record->system_status,
            // 非實作題：系統已批改，學生端不顯示待審（回 null 讓前端顯示 —）
            'teacher_status' => $needsTeacherReview ? $record->teacher_status : null,
            'needs_teacher_review' => $needsTeacherReview,
            'ai_feedback' => $feedbackContent,
            'subs' => $record->subs
                ->map(fn ($sub) => [
                    'id' => $sub->id,
                    'sub_id' => $sub->sub_id,
                    'answer' => $sub->answer,
                    'is_right' => (bool) $sub->is_right,
                    'solo' => (int) $sub->solo,
                ])
                ->values()
                ->all(),
            'created_at' => $record->created_at,
        ];
    }

    private function formatStoredResult(?string $result): mixed
    {
        if ($result === null || $result === '') {
            return $result;
        }

        if (! str_starts_with(ltrim($result), '{') && ! str_starts_with(ltrim($result), '[')) {
            return $result;
        }

        $decoded = json_decode($result, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $result;
    }
}
