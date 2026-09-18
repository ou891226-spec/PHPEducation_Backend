<?php

namespace App\Services;

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

        return QuestionRecord::query()
            ->with(['student', 'question', 'subs'])
            ->whereHas('question', fn ($query) => $query->where('course_id', $courseId))
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
            ->with(['student', 'question', 'subs'])
            ->whereKey($recordId)
            ->whereHas('question.course', fn ($query) => $query->where('teacher_id', $teacher->id))
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

            $passed = $this->bloomMeetsRequirement($bloomId, $record->question?->bloom_id);
            $record->update([
                'bloom_id' => $bloomId,
                'solo' => $passed ? QuestionRecord::SOLO_CORRECT : QuestionRecord::SOLO_WRONG,
                'system_status' => $passed ? QuestionRecord::STATUS_CORRECT : QuestionRecord::STATUS_WRONG,
                'teacher_status' => $passed ? QuestionRecord::STATUS_CORRECT : QuestionRecord::STATUS_WRONG,
            ]);
        } else {
            $solo = $data['solo'] ?? null;
            if (! in_array($solo, [QuestionRecord::SOLO_WRONG, QuestionRecord::SOLO_CORRECT], true)) {
                throw ValidationException::withMessages([
                    'solo' => ['請輸入 1（錯）或 2（對）。'],
                ]);
            }

            $record->update([
                'solo' => $solo,
                'teacher_status' => $solo === QuestionRecord::SOLO_CORRECT
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
        $graded = $this->bloomLevel($gradedBloomId);
        $required = $this->bloomLevel($requiredBloomId);

        if ($graded === null || $required === null) {
            throw ValidationException::withMessages([
                'bloom_id' => ['Bloom 編碼無法判定。'],
            ]);
        }

        return $graded >= $required;
    }

    private function bloomLevel(?string $bloomId): ?int
    {
        if ($bloomId === null || ! preg_match('/^B([1-6])([1-3])?$/i', $bloomId, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRecord(QuestionRecord $record): array
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
