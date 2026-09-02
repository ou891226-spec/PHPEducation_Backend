<?php

namespace App\Services;

use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionSubAnswer;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherQuestionService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForCourse(Teacher $teacher, int $courseId): array
    {
        $course = $this->ownedCourse($teacher, $courseId);

        return $course->questions()
            ->with(['options', 'subAnswers', 'knowledgeCards:id,title,example'])
            ->orderBy('id')
            ->get()
            ->map(fn (Question $question) => $this->formatQuestion($question))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(Teacher $teacher, int $courseId, array $data): array
    {
        $course = $this->ownedCourse($teacher, $courseId);
        $cardIds = $this->ownedCardIds($course, $data['knowledge_card_ids']);

        $question = DB::transaction(function () use ($teacher, $course, $data, $cardIds): Question {
            $question = Question::query()->create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'title' => $data['title'],
                'type' => $data['type'],
                'question_content' => $data['question_content'],
                'bloom_id' => $data['bloom_id'],
                'description' => $data['description'] ?? null,
                'show_example' => (bool) ($data['show_example'] ?? false),
                'starter_code' => $this->codingField($data, 'starter_code'),
                'expected_output' => $this->codingField($data, 'expected_output'),
                'reference_answer' => $this->codingField($data, 'reference_answer'),
            ]);

            $this->syncChildren($question, $data);
            $question->knowledgeCards()->sync($cardIds);

            return $question;
        });

        return $this->formatQuestion($this->loadQuestion($question->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function findForTeacher(Teacher $teacher, int $questionId): array
    {
        return $this->formatQuestion($this->ownedQuestion($teacher, $questionId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(Teacher $teacher, int $questionId, array $data): array
    {
        $question = $this->ownedQuestion($teacher, $questionId);
        $cardIds = $this->ownedCardIds($question->course, $data['knowledge_card_ids']);

        DB::transaction(function () use ($question, $data, $cardIds): void {
            $question->update([
                'title' => $data['title'],
                'type' => $data['type'],
                'question_content' => $data['question_content'],
                'bloom_id' => $data['bloom_id'],
                'description' => $data['description'] ?? null,
                'show_example' => (bool) ($data['show_example'] ?? false),
                'starter_code' => $this->codingField($data, 'starter_code'),
                'expected_output' => $this->codingField($data, 'expected_output'),
                'reference_answer' => $this->codingField($data, 'reference_answer'),
            ]);

            $this->syncChildren($question->fresh(), $data);
            $question->knowledgeCards()->sync($cardIds);
        });

        return $this->formatQuestion($this->loadQuestion($question->id));
    }

    public function delete(Teacher $teacher, int $questionId): void
    {
        $question = $this->ownedQuestion($teacher, $questionId);

        if ($question->records()->exists()) {
            throw ValidationException::withMessages([
                'question' => ['此題已有學生作答，無法刪除'],
            ]);
        }

        $question->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncChildren(Question $question, array $data): void
    {
        $question->options()->delete();
        $question->subAnswers()->delete();

        if (in_array($question->type, Question::OPTION_TYPES, true)) {
            foreach ($data['options'] as $option) {
                $isAnswer = (bool) $option['is_answer'];
                $question->options()->create([
                    'title' => $option['title'],
                    'description' => $option['description'] ?? null,
                    'is_answer' => $isAnswer,
                    'solo' => $isAnswer ? QuestionOption::SOLO_CORRECT : QuestionOption::SOLO_WRONG,
                ]);
            }

            return;
        }

        if (in_array($question->type, Question::SUB_ANSWER_TYPES, true)) {
            foreach ($data['sub_answers'] as $sub) {
                $question->subAnswers()->create([
                    'sub_id' => (int) $sub['sub_id'],
                    'answer' => $sub['answer'],
                    'description' => $sub['description'] ?? null,
                    'solo' => isset($sub['solo']) ? (int) $sub['solo'] : QuestionSubAnswer::SOLO_CORRECT,
                ]);
            }
        }
    }

    /**
     * @param  list<int>  $cardIds
     * @return list<int>
     */
    private function ownedCardIds(Course $course, array $cardIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $cardIds)));

        $owned = KnowledgeCard::query()
            ->whereIn('id', $ids)
            ->where(function (Builder $query) use ($course): void {
                $query->whereHas(
                    'unit.chapter.topic',
                    fn (Builder $topic) => $topic->where('course_id', $course->id)
                )->orWhereHas(
                    'units.chapter.topic',
                    fn (Builder $topic) => $topic->where('course_id', $course->id)
                )->orWhereHas(
                    'topic',
                    fn (Builder $topic) => $topic->where('course_id', $course->id)
                )->orWhere(function (Builder $orphaned) use ($course): void {
                    $orphaned->whereNull('unit_id')
                        ->whereHas(
                            'questions',
                            fn (Builder $questions) => $questions->where('course_id', $course->id)
                        );
                });
            })
            ->pluck('id')
            ->all();

        if (count($owned) !== count($ids)) {
            throw ValidationException::withMessages([
                'knowledge_card_ids' => ['知識卡必須屬於此課程。'],
            ]);
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function codingField(array $data, string $key): ?string
    {
        if (($data['type'] ?? null) !== Question::TYPE_CODING) {
            return null;
        }

        $value = $data[$key] ?? null;
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private function ownedCourse(Teacher $teacher, int $courseId): Course
    {
        $course = Course::query()
            ->where('teacher_id', $teacher->id)
            ->whereKey($courseId)
            ->first();

        if ($course === null) {
            throw new ModelNotFoundException();
        }

        return $course;
    }

    private function ownedQuestion(Teacher $teacher, int $questionId): Question
    {
        $question = Question::query()
            ->with(['options', 'subAnswers', 'knowledgeCards:id,title,example', 'course'])
            ->whereKey($questionId)
            ->whereHas('course', fn (Builder $course) => $course->where('teacher_id', $teacher->id))
            ->first();

        if ($question === null) {
            throw new ModelNotFoundException();
        }

        return $question;
    }

    private function loadQuestion(int $questionId): Question
    {
        return Question::query()
            ->with(['options', 'subAnswers', 'knowledgeCards:id,title,example'])
            ->findOrFail($questionId);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatQuestion(Question $question): array
    {
        return [
            'id' => $question->id,
            'course_id' => $question->course_id,
            'teacher_id' => $question->teacher_id,
            'title' => $question->title,
            'type' => $question->type,
            'question_content' => $question->question_content,
            'bloom_id' => $question->bloom_id,
            'description' => $question->description,
            'show_example' => (bool) $question->show_example,
            'starter_code' => $question->starter_code,
            'expected_output' => $question->expected_output,
            'reference_answer' => $question->reference_answer,
            'knowledge_card_ids' => $question->knowledgeCards->pluck('id')->values()->all(),
            'knowledge_cards' => $question->knowledgeCards
                ->map(fn (KnowledgeCard $card) => [
                    'id' => $card->id,
                    'title' => $card->title,
                    'example' => $card->example,
                ])
                ->values()
                ->all(),
            'options' => $question->options
                ->map(fn (QuestionOption $option) => [
                    'id' => $option->id,
                    'title' => $option->title,
                    'description' => $option->description,
                    'is_answer' => (bool) $option->is_answer,
                    'solo' => (int) $option->solo,
                ])
                ->values()
                ->all(),
            'sub_answers' => $question->subAnswers
                ->map(fn (QuestionSubAnswer $sub) => [
                    'id' => $sub->id,
                    'sub_id' => $sub->sub_id,
                    'answer' => $sub->answer,
                    'description' => $sub->description,
                    'solo' => (int) $sub->solo,
                ])
                ->values()
                ->all(),
            'created_at' => $question->created_at,
            'updated_at' => $question->updated_at,
        ];
    }
}
