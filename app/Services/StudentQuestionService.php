<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionRecord;
use App\Models\QuestionSubAnswer;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentQuestionService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForStudent(Student $student, int $courseId, ?int $knowledgeCardId = null): array
    {
        $this->enrolledCourseId($student, $courseId);

        $query = Question::query()
            ->where('course_id', $courseId)
            ->with(['options', 'subAnswers', 'knowledgeCards:id,title,example'])
            ->orderBy('id');

        if ($knowledgeCardId !== null) {
            $query->whereHas('knowledgeCards', fn (Builder $cards) => $cards->whereKey($knowledgeCardId));
        }

        return $query->get()
            ->map(fn (Question $question) => $this->formatQuestionForStudent($question))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function findForStudent(Student $student, int $questionId): array
    {
        return $this->formatQuestionForStudent($this->enrolledQuestion($student, $questionId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function submit(Student $student, int $questionId, array $data): array
    {
        $question = $this->enrolledQuestion($student, $questionId);

        return match ($question->type) {
            Question::TYPE_CHOICE, Question::TYPE_TRUE_FALSE => $this->submitChoice($student, $question, $data),
            Question::TYPE_FILL, Question::TYPE_DEBUG, Question::TYPE_INTERPRET => $this->submitSubAnswers($student, $question, $data),
            Question::TYPE_CODING => $this->submitCoding($student, $question, $data),
            default => throw ValidationException::withMessages([
                'type' => ['不支援的題型。'],
            ]),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function submitChoice(Student $student, Question $question, array $data): array
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
    private function submitSubAnswers(Student $student, Question $question, array $data): array
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

        $record = DB::transaction(function () use ($student, $question, $submitted, $keys): QuestionRecord {
            $correctCount = 0;
            $rows = [];

            foreach ($keys as $key) {
                $studentAnswer = $submitted[$key->sub_id] ?? '';
                $isRight = $studentAnswer !== '' && trim($studentAnswer) === trim((string) $key->answer);
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
            $parentSolo = $correctCount === 0
                ? QuestionRecord::SOLO_WRONG
                : ($allCorrect ? QuestionRecord::SOLO_ALL_CORRECT : QuestionRecord::SOLO_PARTIAL);

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
    private function submitCoding(Student $student, Question $question, array $data): array
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
        );

        $this->gradeCodingByAi($question, $record);

        return [
            'message' => '已提交',
            'record' => $this->formatRecord($record),
            'system_status' => QuestionRecord::STATUS_PENDING,
        ];
    }

    /**
     * 實作題 AI 批改預留。尚未提供題目範例，目前不執行；先由老師輸入 Bloom 判定。
     */
    private function gradeCodingByAi(Question $question, QuestionRecord $record): void
    {
        //
    }

    private function storeRecord(
        Student $student,
        Question $question,
        string $result,
        string $systemStatus,
        ?int $solo = null,
    ): QuestionRecord {
        return QuestionRecord::query()->create([
            'student_id' => $student->id,
            'question_id' => $question->id,
            'result' => $result,
            'system_status' => $systemStatus,
            'teacher_status' => QuestionRecord::STATUS_PENDING,
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

    private function enrolledQuestion(Student $student, int $questionId): Question
    {
        $question = Question::query()
            ->with(['options', 'subAnswers', 'knowledgeCards:id,title,example'])
            ->whereKey($questionId)
            ->first();

        if ($question === null) {
            throw new ModelNotFoundException();
        }

        $this->enrolledCourseId($student, (int) $question->course_id);

        return $question;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatQuestionForStudent(Question $question): array
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
            'examples' => $question->knowledgeCards
                ->pluck('example')
                ->filter(fn ($example) => is_string($example) && $example !== '')
                ->unique()
                ->values()
                ->all(),
        ];

        if ($question->usesOptions()) {
            $payload['options'] = $question->options
                ->map(fn (QuestionOption $option) => [
                    'id' => $option->id,
                    'title' => $option->title,
                    'description' => $option->description,
                ])
                ->values()
                ->all();
        }

        if ($question->usesSubAnswers()) {
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
            'solo' => $record->solo,
            'system_status' => $record->system_status,
            'teacher_status' => $record->teacher_status,
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
