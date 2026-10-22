<?php

namespace App\Services;

use App\Models\Bloom;
use App\Models\Question;
use App\Models\QuestionRecord;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class TeacherQuestionRecordService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForTeacher(Teacher $teacher, int $courseId): array
    {
        $this->ownedCourse($teacher, $courseId);

        // 即學即測可無限重試，只算學生自我檢核，不列入老師的作答紀錄
        return QuestionRecord::query()
            ->with(['student', 'question', 'subs', 'aiFeedback'])
            ->whereHas('question', fn ($query) => $query->where('course_id', $courseId))
            ->where('source', QuestionRecord::SOURCE_PRACTICE)
            ->orderByDesc('id')
            ->get()
            ->map(fn (QuestionRecord $record) => $this->formatRecord($record))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function review(Teacher $teacher, int $recordId, array $data): array
    {
        $record = QuestionRecord::query()
            ->with(['student', 'question', 'subs', 'aiFeedback'])
            ->whereKey($recordId)
            ->whereHas('question.course', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->where('source', QuestionRecord::SOURCE_PRACTICE)
            ->first();

        if ($record === null) {
            throw new ModelNotFoundException();
        }

        if ($record->question?->type === Question::TYPE_CODING) {
            $bloomId = $data['bloom_id'] ?? null;
            if (! is_string($bloomId) || $bloomId === '') {
                throw ValidationException::withMessages([
                    'bloom_id' => ['實作題請輸入 Bloom 編碼。'],
                ]);
            }

            // SOLO 使用教師送來的值
            $solo = $data['solo'] ?? null;
            if ($solo === null) {
                throw ValidationException::withMessages([
                    'solo' => ['實作題請選擇 SOLO 等級 S1 至 S5。'],
                ]);
            }

            $passed = $this->bloomMeetsRequirement($bloomId, $record->question?->bloom_id);
            $record->update([
                'bloom_id' => $bloomId,
                'solo' => (int) $solo,
                'system_status' => $passed ? QuestionRecord::STATUS_CORRECT : QuestionRecord::STATUS_WRONG,
                'teacher_status' => $passed ? QuestionRecord::STATUS_CORRECT : QuestionRecord::STATUS_WRONG,
            ]);
        } else {
            $solo = isset($data['solo']) ? (int) $data['solo'] : null;
            if ($solo === null) {
                throw ValidationException::withMessages([
                    'solo' => ['請選擇 SOLO 層級（S1～S5）。'],
                ]);
            }

            // 覆核時採用教師選定的整題 SOLO；選項與填空題不按作答正誤重算編碼。
            $record->update([
                'solo' => $solo,
                'teacher_status' => $solo >= $record->question->fullCorrectSolo()
                    ? QuestionRecord::STATUS_CORRECT
                    : QuestionRecord::STATUS_WRONG,
            ]);
        }

        return $this->formatRecord($record->fresh(['student', 'question', 'subs']));
    }

    private function ownedCourse(Teacher $teacher, int $courseId): void
    {
        $owned = $teacher->courses()->whereKey($courseId)->exists();
        if (! $owned) {
            throw new ModelNotFoundException();
        }
    }

    private function bloomMeetsRequirement(string $gradedBloomId, ?string $requiredBloomId): bool
    {
        $graded = Bloom::levelOf($gradedBloomId);
        $required = Bloom::levelOf($requiredBloomId);

        if ($graded === null || $required === null) {
            throw ValidationException::withMessages([
                'bloom_id' => ['Bloom 編碼無法判定。'],
            ]);
        }

        return $graded >= $required;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatRecord(QuestionRecord $record): array
    {
        return [
            'id' => $record->id,
            'student_id' => $record->student_id,
            'student_no' => $record->student?->student_no,
            'student_name' => $record->student?->name,
            'question_id' => $record->question_id,
            'question_title' => $record->question?->title,
            'question_type' => $record->question?->type,
            'result' => $this->formatStoredResult($record->result),
            'solo' => $record->solo,
            'bloom_id' => $record->bloom_id,
            'question_bloom_id' => $record->question?->bloom_id,
            'starter_code' => $record->question?->starter_code,
            'expected_output' => $record->question?->expected_output,
            'reference_answer' => $record->question?->reference_answer,
            'system_status' => $record->system_status,
            'teacher_status' => $record->teacher_status,
            'ai_feedback' => $record->aiFeedback
                ? (json_decode($record->aiFeedback->feedback_content, true) ?? $record->aiFeedback->feedback_content)
                : null,
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
