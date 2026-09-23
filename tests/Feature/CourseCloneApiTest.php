<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Enrollment;
use App\Models\KnowledgeCard;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionRecord;
use App\Models\QuestionSubAnswer;
use App\Models\Student;
use App\Models\Unit;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseCloneApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_create_blank_course_still_works(): void
    {
        $token = $this->teacherToken();

        $this->withToken($token)
            ->postJson('/api/v1/teacher/courses', [
                'name' => '空白新課',
                'description' => '不帶入',
                'semester' => '115-1',
                'class_name' => '資管一甲',
            ])
            ->assertCreated()
            ->assertJsonPath('course.name', '空白新課')
            ->assertJsonPath('course.class_name', '資管一甲');
    }

    public function test_rejects_copy_questions_without_materials(): void
    {
        $token = $this->teacherToken();
        $sourceId = $this->createSourceCourse($token);

        $this->withToken($token)
            ->postJson('/api/v1/teacher/courses', [
                'name' => '非法',
                'description' => '只拷題',
                'semester' => '115-1',
                'class_name' => '資管二甲',
                'source_course_id' => $sourceId,
                'copy_materials' => false,
                'copy_questions' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['copy_questions']);
    }

    public function test_rejects_copy_flags_without_source_course(): void
    {
        $this->withToken($this->teacherToken())
            ->postJson('/api/v1/teacher/courses', [
                'name' => '缺來源',
                'description' => '缺來源',
                'semester' => '115-1',
                'class_name' => '資管二甲',
                'copy_materials' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['source_course_id']);
    }

    public function test_cannot_clone_another_teachers_course(): void
    {
        $teacherToken = $this->teacherToken();
        $sourceId = $this->createSourceCourse($teacherToken);

        $otherToken = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($otherToken)
            ->postJson('/api/v1/teacher/courses', [
                'name' => '偷拷',
                'description' => '跨老師',
                'semester' => '115-1',
                'class_name' => '資管二乙',
                'source_course_id' => $sourceId,
                'copy_materials' => true,
            ])
            ->assertNotFound();
    }

    public function test_deep_copies_materials_and_questions_with_new_ids(): void
    {
        $token = $this->teacherToken();
        $sourceId = $this->createSourceCourse($token);
        $sourceCardId = KnowledgeCard::query()->where('course_id', $sourceId)->value('id');
        $sourceQuestionId = Question::query()->where('course_id', $sourceId)->value('id');
        $sourceChapterId = Chapter::query()->where('course_id', $sourceId)->value('id');
        $sourceUnitId = Unit::query()->where('chapter_id', $sourceChapterId)->value('id');

        $response = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '新學期課',
            'description' => '帶入教材與題目',
            'semester' => '115-2',
            'class_name' => '資管二乙',
            'source_course_id' => $sourceId,
            'copy_materials' => true,
            'copy_questions' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('course.name', '新學期課')
            ->assertJsonPath('course.class_name', '資管二乙');

        $newCourseId = (int) $response->json('course.id');
        $this->assertNotSame($sourceId, $newCourseId);

        $newChapter = Chapter::query()->where('course_id', $newCourseId)->first();
        $this->assertNotNull($newChapter);
        $this->assertNotSame($sourceChapterId, $newChapter->id);
        $this->assertSame('第一章', $newChapter->name);

        $newUnit = Unit::query()->where('chapter_id', $newChapter->id)->first();
        $this->assertNotNull($newUnit);
        $this->assertNotSame($sourceUnitId, $newUnit->id);

        $newCard = KnowledgeCard::query()->where('course_id', $newCourseId)->first();
        $this->assertNotNull($newCard);
        $this->assertNotSame($sourceCardId, $newCard->id);
        $this->assertSame($newUnit->id, $newCard->unit_id);
        $this->assertSame('變數', $newCard->title);

        $this->assertTrue(
            DB::table('knowledge_card_unit')
                ->where('unit_id', $newUnit->id)
                ->where('knowledge_card_id', $newCard->id)
                ->exists(),
        );

        $newQuestion = Question::query()->where('course_id', $newCourseId)->first();
        $this->assertNotNull($newQuestion);
        $this->assertNotSame($sourceQuestionId, $newQuestion->id);
        $this->assertSame('變數選擇', $newQuestion->title);

        $this->assertSame(
            [$newCard->id],
            $newQuestion->knowledgeCards()->pluck('knowledge_cards.id')->all(),
        );

        $this->assertSame(2, QuestionOption::query()->where('question_id', $newQuestion->id)->count());
        $this->assertSame(0, QuestionSubAnswer::query()->where('question_id', $newQuestion->id)->count());

        // 來源完全不動
        $this->assertSame(1, Chapter::query()->where('course_id', $sourceId)->count());
        $this->assertSame(1, KnowledgeCard::query()->where('course_id', $sourceId)->whereKey($sourceCardId)->count());
        $this->assertSame(1, Question::query()->where('course_id', $sourceId)->whereKey($sourceQuestionId)->count());
    }

    public function test_copy_materials_only_skips_questions_and_students(): void
    {
        $token = $this->teacherToken();
        $sourceId = $this->createSourceCourse($token);

        $student = Student::query()->firstOrFail();
        Enrollment::query()->create([
            'course_id' => $sourceId,
            'student_id' => $student->id,
        ]);

        $sourceQuestion = Question::query()->where('course_id', $sourceId)->firstOrFail();
        QuestionRecord::query()->create([
            'student_id' => $student->id,
            'question_id' => $sourceQuestion->id,
            'result' => '0',
            'system_status' => QuestionRecord::STATUS_WRONG,
            'teacher_status' => QuestionRecord::STATUS_PENDING,
            'solo' => QuestionRecord::SOLO_WRONG,
            'bloom_id' => 'B1',
        ]);

        $newCourseId = (int) $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '只拷教材',
            'description' => '不含題與學生',
            'semester' => '115-2',
            'class_name' => '資管二丙',
            'source_course_id' => $sourceId,
            'copy_materials' => true,
            'copy_questions' => false,
        ])->json('course.id');

        $this->assertSame(1, Chapter::query()->where('course_id', $newCourseId)->count());
        $this->assertSame(1, KnowledgeCard::query()->where('course_id', $newCourseId)->count());
        $this->assertSame(0, Question::query()->where('course_id', $newCourseId)->count());
        $this->assertSame(0, Enrollment::query()->where('course_id', $newCourseId)->count());
        $this->assertSame(
            0,
            QuestionRecord::query()
                ->whereHas('question', fn ($query) => $query->where('course_id', $newCourseId))
                ->count(),
        );
        $this->assertSame(1, QuestionRecord::query()->where('question_id', $sourceQuestion->id)->count());
    }

    public function test_editing_cloned_card_does_not_affect_source(): void
    {
        $token = $this->teacherToken();
        $sourceId = $this->createSourceCourse($token);
        $sourceCard = KnowledgeCard::query()->where('course_id', $sourceId)->firstOrFail();

        $newCourseId = (int) $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '獨立副本',
            'description' => '改新不改舊',
            'semester' => '115-2',
            'class_name' => '資管二丁',
            'source_course_id' => $sourceId,
            'copy_materials' => true,
        ])->json('course.id');

        $newCard = KnowledgeCard::query()->where('course_id', $newCourseId)->firstOrFail();

        $this->withToken($token)
            ->putJson("/api/v1/teacher/knowledge-cards/{$newCard->id}", [
                'title' => '改過的標題',
                'content' => '新內容',
            ])
            ->assertOk();

        $this->assertSame('變數', $sourceCard->fresh()->title);
        $this->assertSame('改過的標題', $newCard->fresh()->title);
    }

    private function createSourceCourse(string $token): int
    {
        $courseId = (int) $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '來源課',
            'description' => '來源',
            'semester' => '115-1',
            'class_name' => '資管二甲',
        ])->json('course.id');

        $chapterId = (int) $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/chapters", [
            'name' => '第一章',
        ])->json('chapter.id');

        $unitId = (int) $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
            'name' => '變數',
        ])->json('unit.id');

        $cardId = (int) $this->withToken($token)->postJson("/api/v1/teacher/units/{$unitId}/knowledge-cards", [
            'title' => '變數',
            'content' => '變數說明',
            'example' => '$a = 1;',
        ])->json('knowledge_card.id');

        $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
            'title' => '變數選擇',
            'type' => Question::TYPE_CHOICE,
            'question_content' => '何者正確？',
            'bloom_id' => 'B1',
            'knowledge_card_ids' => [$cardId],
            'options' => [
                ['title' => 'A', 'is_answer' => true],
                ['title' => 'B', 'is_answer' => false],
            ],
        ])->assertCreated();

        return $courseId;
    }

    private function teacherToken(): string
    {
        return $this->loginToken('teacher@school.edu.tw');
    }

    private function loginToken(string $account): string
    {
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        $response = $this->postJson('/api/v1/auth/login', [
            'account' => $account,
            'password' => self::PASSWORD,
        ]);

        $response->assertOk();

        return $response->json('token');
    }
}
