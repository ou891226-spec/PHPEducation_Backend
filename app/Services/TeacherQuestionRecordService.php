<?php

namespace App\Services;

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
            ->with(['student', 'question', 'mapping'])
            ->whereHas('question', fn ($query) => $query->where('course_id', $courseId))
            ->orderByDesc('id')
            ->get()
            ->map(fn (QuestionRecord $record) => $this->formatRecord($record))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function review(Teacher $teacher, int $recordId, string $teacherStatus): array
    {
        if (! in_array($teacherStatus, [QuestionRecord::STATUS_CORRECT, QuestionRecord::STATUS_WRONG], true)) {
            throw ValidationException::withMessages([
                'teacher_status' => ['只能為 correct 或 wrong。'],
            ]);
        }

        $record = QuestionRecord::query()
            ->with(['student', 'question', 'mapping'])
            ->whereKey($recordId)
            ->whereHas('question.course', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->first();

        if ($record === null) {
            throw new ModelNotFoundException();
        }

        $record->update(['teacher_status' => $teacherStatus]);

        return $this->formatRecord($record->fresh(['student', 'question', 'mapping']));
    }

    private function ownedCourse(Teacher $teacher, int $courseId): void
    {
        $owned = $teacher->courses()->whereKey($courseId)->exists();
        if (! $owned) {
            throw new ModelNotFoundException();
        }
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
            'result' => $record->result,
            'question_mapping_id' => $record->question_mapping_id,
            'bloom_id' => $record->mapping?->bloom_id,
            'solo_id' => $record->mapping?->solo_id,
            'system_status' => $record->system_status,
            'teacher_status' => $record->teacher_status,
            'created_at' => $record->created_at,
        ];
    }
}
