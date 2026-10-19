<?php

namespace App\Services;

use App\Models\Bloom;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionRecord;
use App\Models\QuestionSubAnswer;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * 教師端數據統計：依單元列出 Bloom、SOLO 次數。
 * 題目掛的知識卡在多個單元時，每個相關單元各算一次；合計每題只算一次。
 */
class TeacherStatsService
{
    public const SOLO_CODES = [1 => 'S1', 2 => 'S2', 3 => 'S3', 4 => 'S4', 5 => 'S5'];

    /**
     * 根據教材：每個 Bloom 編碼有幾題。
     *
     * @return array<string, mixed>
     */
    public function bloom(Teacher $teacher, int $courseId): array
    {
        $course = $this->ownedCourse($teacher, $courseId);
        $columns = Bloom::teacherChoiceIds();

        $items = $this->courseQuestions($course)
            ->filter(fn (Question $question) => in_array($question->bloom_id, $columns, true))
            ->map(fn (Question $question) => [
                'unit_ids' => $this->unitIdsOf($question),
                'column' => $question->bloom_id,
            ])
            ->values()
            ->all();

        return $this->table($course, $columns, $items);
    }

    /**
     * 根據教材：每題標註的最高 SOLO（選項、子題答案取最高）；沒有標註的實作題不算。
     *
     * @return array<string, mixed>
     */
    public function questionSolo(Teacher $teacher, int $courseId): array
    {
        $course = $this->ownedCourse($teacher, $courseId);

        $items = [];
        foreach ($this->courseQuestions($course) as $question) {
            $solo = max([
                0,
                ...$question->options->map(fn (QuestionOption $option) => (int) $option->solo)->all(),
                ...$question->subAnswers->map(fn (QuestionSubAnswer $sub) => (int) $sub->solo)->all(),
            ]);

            if (isset(self::SOLO_CODES[$solo])) {
                $items[] = ['unit_ids' => $this->unitIdsOf($question), 'column' => self::SOLO_CODES[$solo]];
            }
        }

        return $this->table($course, array_values(self::SOLO_CODES), $items);
    }

    /**
     * 根據學生作答：正式題目作答的 SOLO 次數；給 studentId 只看該生，否則全班。小試身手、待批改不算。
     *
     * @return array<string, mixed>
     */
    public function studentSolo(Teacher $teacher, int $courseId, ?int $studentId = null): array
    {
        $course = $this->ownedCourse($teacher, $courseId);

        $students = $course->students()
            ->orderBy('student_no')
            ->get(['students.id', 'students.student_no', 'students.name']);

        $student = null;
        if ($studentId !== null) {
            $student = $students->firstWhere('id', $studentId);
            if ($student === null) {
                throw new ModelNotFoundException();
            }
        }

        $unitIdsByQuestion = $this->courseQuestions($course)
            ->mapWithKeys(fn (Question $question) => [$question->id => $this->unitIdsOf($question)]);

        $items = QuestionRecord::query()
            ->where('source', QuestionRecord::SOURCE_PRACTICE)
            ->whereIn('question_id', $unitIdsByQuestion->keys())
            ->whereIn('student_id', $student !== null ? [$student->id] : $students->pluck('id'))
            ->whereIn('solo', array_keys(self::SOLO_CODES))
            ->get(['question_id', 'solo'])
            ->map(fn (QuestionRecord $record) => [
                'unit_ids' => $unitIdsByQuestion[$record->question_id],
                'column' => self::SOLO_CODES[(int) $record->solo],
            ])
            ->all();

        return [
            ...$this->table($course, array_values(self::SOLO_CODES), $items),
            'student' => $student === null ? null : $this->formatStudent($student),
            'students' => $students->map(fn (Student $row) => $this->formatStudent($row))->values()->all(),
        ];
    }

    /**
     * @param  list<string>  $columns
     * @param  list<array{unit_ids: list<int>, column: string}>  $items
     * @return array<string, mixed>
     */
    private function table(Course $course, array $columns, array $items): array
    {
        $empty = array_fill_keys($columns, 0);
        $unitCounts = [];
        $totals = $empty;

        foreach ($items as $item) {
            $totals[$item['column']]++;
            foreach ($item['unit_ids'] as $unitId) {
                $unitCounts[$unitId] ??= $empty;
                $unitCounts[$unitId][$item['column']]++;
            }
        }

        $rows = [];
        foreach ($course->chapters as $chapter) {
            foreach ($chapter->units as $unit) {
                $counts = $unitCounts[$unit->id] ?? $empty;
                $rows[] = [
                    'chapter_id' => $chapter->id,
                    'chapter_name' => $chapter->name,
                    'unit_id' => $unit->id,
                    'unit_name' => $unit->name,
                    'unit_status' => $unit->status,
                    'counts' => $counts,
                    'total' => array_sum($counts),
                ];
            }
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'totals' => ['counts' => $totals, 'total' => array_sum($totals)],
        ];
    }

    /**
     * @return Collection<int, Question>
     */
    private function courseQuestions(Course $course): Collection
    {
        return $course->questions()
            ->with(['options:id,question_id,solo', 'subAnswers:id,question_id,solo', 'knowledgeCards.units:units.id'])
            ->orderBy('id')
            ->get(['id', 'course_id', 'type', 'bloom_id']);
    }

    /**
     * @return list<int>
     */
    private function unitIdsOf(Question $question): array
    {
        return $question->knowledgeCards
            ->flatMap(fn ($card) => $card->units->pluck('id'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, student_no: string|null, name: string|null}
     */
    private function formatStudent(Student $student): array
    {
        return [
            'id' => $student->id,
            'student_no' => $student->student_no,
            'name' => $student->name,
        ];
    }

    private function ownedCourse(Teacher $teacher, int $courseId): Course
    {
        $course = Course::query()
            ->with('chapters.units:id,chapter_id,name,status,sort_order')
            ->where('teacher_id', $teacher->id)
            ->whereKey($courseId)
            ->first();

        if ($course === null) {
            throw new ModelNotFoundException();
        }

        return $course;
    }
}
