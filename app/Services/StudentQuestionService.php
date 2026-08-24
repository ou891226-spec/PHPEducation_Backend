<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionRecord;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            ->with(['options', 'knowledgeCards:id'])
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
        $question = $this->enrolledQuestion($student, $questionId);

        return $this->formatQuestionForStudent($question);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function submit(Student $student, int $questionId, array $data): array
    {
        $question = $this->enrolledQuestion($student, $questionId);
        $mappingId = $this->mappingId($question);

        return match ($question->type) {
            Question::TYPE_CHOICE => $this->submitChoice($student, $question, $mappingId, $data),
            Question::TYPE_DEBUG => $this->submitDebug($student, $question, $mappingId, $data),
            Question::TYPE_CODING => $this->submitCoding($student, $question, $mappingId, $data),
            default => throw ValidationException::withMessages([
                'type' => ['不支援的題型。'],
            ]),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function submitChoice(Student $student, Question $question, int $mappingId, array $data): array
    {
        $optionId = $data['option_id'] ?? null;
        if ($optionId === null) {
            throw ValidationException::withMessages([
                'option_id' => ['選擇題請傳送 option_id。'],
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
            $mappingId,
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
    private function submitDebug(Student $student, Question $question, int $mappingId, array $data): array
    {
        $hasLine = array_key_exists('code_line', $data) && $data['code_line'] !== null && $data['code_line'] !== '';
        $hasAnswer = array_key_exists('answer', $data) && $data['answer'] !== null && $data['answer'] !== '';

        if (! $hasLine && ! $hasAnswer) {
            throw ValidationException::withMessages([
                'code_line' => ['除錯題請傳送 code_line 或 answer。'],
            ]);
        }

        $subInfo = $question->debugSubInfo;
        if ($subInfo === null) {
            throw ValidationException::withMessages([
                'question_id' => ['此題尚未設定除錯資訊。'],
            ]);
        }

        $lineOk = ! $hasLine || (int) $data['code_line'] === (int) $subInfo->code_line;
        $answerOk = ! $hasAnswer || trim((string) $data['answer']) === trim((string) $subInfo->answer);
        $correct = $lineOk && $answerOk;

        $payload = [];
        if ($hasLine) {
            $payload['code_line'] = (int) $data['code_line'];
        }
        if ($hasAnswer) {
            $payload['answer'] = (string) $data['answer'];
        }

        $record = $this->storeRecord(
            $student,
            $question,
            $mappingId,
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            $correct ? QuestionRecord::STATUS_CORRECT : QuestionRecord::STATUS_WRONG,
        );

        return [
            'message' => $correct ? '正確' : '錯誤',
            'record' => $this->formatRecord($record),
            'system_status' => $record->system_status,
            'description' => $subInfo->description,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function submitCoding(Student $student, Question $question, int $mappingId, array $data): array
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
            $mappingId,
            $code,
            QuestionRecord::STATUS_PENDING,
        );

        return [
            'message' => '已提交',
            'record' => $this->formatRecord($record),
            'system_status' => QuestionRecord::STATUS_PENDING,
        ];
    }

    private function storeRecord(
        Student $student,
        Question $question,
        int $mappingId,
        string $result,
        string $systemStatus,
    ): QuestionRecord {
        return QuestionRecord::query()->create([
            'student_id' => $student->id,
            'question_id' => $question->id,
            'result' => $result,
            'question_mapping_id' => $mappingId,
            'system_status' => $systemStatus,
            'teacher_status' => QuestionRecord::STATUS_PENDING,
        ]);
    }

    private function mappingId(Question $question): int
    {
        $mapping = $question->bloomSoloMappings()->orderBy('id')->first();
        if ($mapping === null) {
            throw ValidationException::withMessages([
                'question_id' => ['此題尚未設定 Bloom/SOLO。'],
            ]);
        }

        return (int) $mapping->id;
    }

    private function enrolledCourseId(Student $student, int $courseId): int
    {
        $enrolled = $student->courses()->where('courses.id', $courseId)->exists();
        if (! $enrolled) {
            throw new ModelNotFoundException();
        }

        return $courseId;
    }

    private function enrolledQuestion(Student $student, int $questionId): Question
    {
        $question = Question::query()
            ->with(['options', 'debugSubInfo', 'knowledgeCards:id'])
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
            'knowledge_card_ids' => $question->knowledgeCards->pluck('id')->values()->all(),
        ];

        if ($question->type === Question::TYPE_CHOICE) {
            $payload['options'] = $question->options
                ->map(fn (QuestionOption $option) => [
                    'id' => $option->id,
                    'title' => $option->title,
                    'description' => $option->description,
                ])
                ->values()
                ->all();
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
            'result' => $record->result,
            'question_mapping_id' => $record->question_mapping_id,
            'system_status' => $record->system_status,
            'teacher_status' => $record->teacher_status,
        ];
    }
}
