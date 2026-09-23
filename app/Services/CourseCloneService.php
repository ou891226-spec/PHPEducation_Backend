<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionSubAnswer;
use App\Models\Teacher;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 建立新課程時，從教師自己的既有課深拷貝教材／題目（獨立新 ID，不共用）。
 */
class CourseCloneService
{
    /**
     * @param  array{copy_materials?: bool, copy_questions?: bool}  $flags
     */
    public function cloneInto(Teacher $teacher, Course $source, Course $target, array $flags): void
    {
        $copyMaterials = (bool) ($flags['copy_materials'] ?? false);
        $copyQuestions = (bool) ($flags['copy_questions'] ?? false);

        if (! $copyMaterials && ! $copyQuestions) {
            return;
        }

        $cardMap = [];

        if ($copyMaterials) {
            $cardMap = $this->cloneMaterials($source, $target);
        }

        if ($copyQuestions) {
            $this->cloneQuestions($teacher, $source, $target, $cardMap);
        }
    }

    /**
     * @return array<int, int> old knowledge_card_id => new knowledge_card_id
     */
    private function cloneMaterials(Course $source, Course $target): array
    {
        /** @var array<int, int> $chapterMap */
        $chapterMap = [];
        /** @var array<int, int> $unitMap */
        $unitMap = [];
        /** @var array<int, int> $cardMap */
        $cardMap = [];

        $chapters = Chapter::query()
            ->where('course_id', $source->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with(['units' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->get();

        foreach ($chapters as $oldChapter) {
            $newChapter = Chapter::query()->create([
                'course_id' => $target->id,
                'name' => $oldChapter->name,
                'sort_order' => $oldChapter->sort_order,
            ]);
            $chapterMap[$oldChapter->id] = $newChapter->id;

            foreach ($oldChapter->units as $oldUnit) {
                $newUnit = Unit::query()->create([
                    'chapter_id' => $newChapter->id,
                    'name' => $oldUnit->name,
                    'sort_order' => $oldUnit->sort_order,
                    'status' => $oldUnit->status === 'draft' ? 'draft' : 'published',
                ]);
                $unitMap[$oldUnit->id] = $newUnit->id;
            }
        }

        $cards = $this->collectSourceCards($source, array_keys($unitMap));

        foreach ($cards as $oldCard) {
            $newUnitId = $oldCard->unit_id !== null
                ? ($unitMap[$oldCard->unit_id] ?? null)
                : null;

            $newCard = KnowledgeCard::query()->create([
                'unit_id' => $newUnitId,
                'course_id' => $target->id,
                'title' => $oldCard->title,
                'type' => $oldCard->type ?? 'keyword',
                'content' => $oldCard->content,
                'example' => $oldCard->example,
                'sort_order' => $oldCard->sort_order,
            ]);
            $cardMap[$oldCard->id] = $newCard->id;
        }

        $this->rebuildCardUnitPivots($unitMap, $cardMap);

        return $cardMap;
    }

    /**
     * @param  list<int>  $sourceUnitIds
     * @return Collection<int, KnowledgeCard>
     */
    private function collectSourceCards(Course $source, array $sourceUnitIds): Collection
    {
        $byCourse = KnowledgeCard::query()
            ->where('course_id', $source->id)
            ->get();

        $byUnit = $sourceUnitIds === []
            ? collect()
            : KnowledgeCard::query()
                ->whereIn('unit_id', $sourceUnitIds)
                ->get();

        $byPivot = $sourceUnitIds === []
            ? collect()
            : KnowledgeCard::query()
                ->whereHas('units', fn ($query) => $query->whereIn('units.id', $sourceUnitIds))
                ->get();

        $byQuestions = KnowledgeCard::query()
            ->whereHas('questions', fn ($query) => $query->where('course_id', $source->id))
            ->get();

        return $byCourse
            ->concat($byUnit)
            ->concat($byPivot)
            ->concat($byQuestions)
            ->unique('id')
            ->sortBy('id')
            ->values();
    }

    /**
     * @param  array<int, int>  $unitMap
     * @param  array<int, int>  $cardMap
     */
    private function rebuildCardUnitPivots(array $unitMap, array $cardMap): void
    {
        if ($unitMap === [] || $cardMap === []) {
            return;
        }

        $rows = DB::table('knowledge_card_unit')
            ->whereIn('unit_id', array_keys($unitMap))
            ->whereIn('knowledge_card_id', array_keys($cardMap))
            ->orderBy('id')
            ->get();

        $now = now();
        $insert = [];

        foreach ($rows as $row) {
            $newUnitId = $unitMap[$row->unit_id] ?? null;
            $newCardId = $cardMap[$row->knowledge_card_id] ?? null;
            if ($newUnitId === null || $newCardId === null) {
                continue;
            }

            $insert[] = [
                'unit_id' => $newUnitId,
                'knowledge_card_id' => $newCardId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($insert !== []) {
            DB::table('knowledge_card_unit')->insert($insert);
        }
    }

    /**
     * @param  array<int, int>  $cardMap
     */
    private function cloneQuestions(Teacher $teacher, Course $source, Course $target, array $cardMap): void
    {
        $questions = Question::query()
            ->where('course_id', $source->id)
            ->with(['options', 'subAnswers', 'knowledgeCards:id'])
            ->orderBy('id')
            ->get();

        foreach ($questions as $oldQuestion) {
            $newQuestion = Question::query()->create([
                'course_id' => $target->id,
                'teacher_id' => $teacher->id,
                'title' => $oldQuestion->title,
                'type' => $oldQuestion->type,
                'question_content' => $oldQuestion->question_content,
                'bloom_id' => $oldQuestion->bloom_id,
                'description' => $oldQuestion->description,
                'show_example' => (bool) $oldQuestion->show_example,
                'starter_code' => $oldQuestion->starter_code,
                'expected_output' => $oldQuestion->expected_output,
                'reference_answer' => $oldQuestion->reference_answer,
            ]);

            foreach ($oldQuestion->options as $oldOption) {
                QuestionOption::query()->create([
                    'question_id' => $newQuestion->id,
                    'title' => $oldOption->title,
                    'description' => $oldOption->description,
                    'is_answer' => $oldOption->is_answer,
                    'solo' => $oldOption->solo,
                ]);
            }

            foreach ($oldQuestion->subAnswers as $oldSub) {
                QuestionSubAnswer::query()->create([
                    'question_id' => $newQuestion->id,
                    'sub_id' => $oldSub->sub_id,
                    'answer' => $oldSub->answer,
                    'description' => $oldSub->description,
                    'solo' => $oldSub->solo,
                ]);
            }

            $newCardIds = [];
            foreach ($oldQuestion->knowledgeCards as $oldCard) {
                if (isset($cardMap[$oldCard->id])) {
                    $newCardIds[] = $cardMap[$oldCard->id];
                }
            }

            if ($newCardIds !== []) {
                $newQuestion->knowledgeCards()->sync($newCardIds);
            }
        }
    }
}
