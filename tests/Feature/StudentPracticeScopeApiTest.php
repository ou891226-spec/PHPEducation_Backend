<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\KnowledgeCardCompletion;
use App\Models\Question;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class StudentPracticeScopeApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_questions_are_classified_by_card_unit_chapter_and_course(): void
    {
        $seed = $this->seedCourse();
        $single = $this->createChoice($seed['course'], [$seed['if']], '單卡');
        $sameUnit = $this->createChoice($seed['course'], [$seed['if'], $seed['switch']], '同單元');
        $sameChapter = $this->createChoice($seed['course'], [$seed['if'], $seed['for']], '同章節');
        $crossChapter = $this->createChoice($seed['course'], [$seed['if'], $seed['function']], '跨章節');
        $noCard = $this->createChoice($seed['course'], [], '沒掛卡');
        $this->createChoice($seed['course'], [$seed['draft']], '未發布');
        $cardQuiz = $this->createChoice($seed['course'], [$seed['if']], '即學即測題', 'B11');

        $response = $this->withToken($this->loginToken('s1411131000'))
            ->getJson("/api/v1/student/courses/{$seed['course']}/questions")
            ->assertOk()
            ->assertJsonCount(5, 'questions')
            ->assertJsonCount(2, 'chapters')
            ->assertJsonPath('chapters.0.units.0.knowledge_cards.0.title', 'if');

        $questions = collect($response->json('questions'))->keyBy('id');

        $this->assertSame('card', $questions[$single->id]['scope']);
        $this->assertSame([$seed['unitA']], $questions[$single->id]['scope_unit_ids']);
        $this->assertSame('unit', $questions[$sameUnit->id]['scope']);
        $this->assertSame([$seed['unitA']], $questions[$sameUnit->id]['scope_unit_ids']);
        $this->assertSame('chapter', $questions[$sameChapter->id]['scope']);
        $this->assertSame([$seed['chapter1']], $questions[$sameChapter->id]['scope_chapter_ids']);
        $this->assertSame('course', $questions[$crossChapter->id]['scope']);
        $this->assertSame('course', $questions[$noCard->id]['scope']);
        $this->assertFalse($questions[$noCard->id]['locked']);
        $this->assertArrayNotHasKey($cardQuiz->id, $questions->all());

        $this->withToken($this->loginToken('s1411131000'))
            ->getJson("/api/v1/student/questions/{$cardQuiz->id}")
            ->assertNotFound();
    }

    public function test_list_returns_summary_and_filters_on_the_server(): void
    {
        $seed = $this->seedCourse();
        $single = $this->createChoice($seed['course'], [$seed['if']], '單卡 if');
        $sameUnit = $this->createChoice($seed['course'], [$seed['if'], $seed['switch']], '同單元');
        $sameChapter = $this->createChoice($seed['course'], [$seed['if'], $seed['for']], '同章節');
        $this->createChoice($seed['course'], [$seed['for']], '單卡 for');
        $token = $this->loginToken('s1411131000');
        $base = "/api/v1/student/courses/{$seed['course']}/questions";

        $this->withToken($token)
            ->getJson($base)
            ->assertOk()
            ->assertJsonPath('questions.0.knowledge_cards.0.title', 'if')
            ->assertJsonMissingPath('questions.0.question_content')
            ->assertJsonMissingPath('questions.0.options')
            ->assertJsonMissingPath('questions.0.knowledge_cards.0.content')
            ->assertJsonMissingPath('meta');

        $this->withToken($token)
            ->getJson("{$base}?scope=card&unit_id={$seed['unitA']}")
            ->assertOk()
            ->assertJsonCount(1, 'questions')
            ->assertJsonPath('questions.0.id', $single->id);

        $this->withToken($token)
            ->getJson("{$base}?scope=unit&chapter_id={$seed['chapter1']}")
            ->assertOk()
            ->assertJsonCount(1, 'questions')
            ->assertJsonPath('questions.0.id', $sameUnit->id);

        $this->withToken($token)
            ->getJson("{$base}?keyword=單卡")
            ->assertOk()
            ->assertJsonCount(2, 'questions');

        $this->withToken($token)
            ->getJson("{$base}?type=fill")
            ->assertOk()
            ->assertJsonCount(0, 'questions');

        $this->withToken($token)
            ->getJson("{$base}?status=unanswered&per_page=2&page=2")
            ->assertOk()
            ->assertJsonCount(2, 'questions')
            ->assertJsonPath('questions.0.id', $sameChapter->id)
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.current_page', 2);

        $this->withToken($token)
            ->getJson("{$base}?status=answered")
            ->assertOk()
            ->assertJsonCount(0, 'questions');

        $this->withToken($token)
            ->getJson("{$base}?scope=bad&per_page=0")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scope', 'per_page']);
    }

    public function test_question_is_locked_until_every_card_is_completed(): void
    {
        $seed = $this->seedCourse();
        $single = $this->createChoice($seed['course'], [$seed['if']], '單卡');
        $sameUnit = $this->createChoice($seed['course'], [$seed['if'], $seed['switch']], '同單元');
        $token = $this->loginToken('s1411131000');

        $this->withToken($token)
            ->getJson("/api/v1/student/questions/{$sameUnit->id}")
            ->assertOk()
            ->assertJsonPath('question.locked', true)
            ->assertJsonCount(2, 'question.locked_cards');

        $this->withToken($token)
            ->postJson("/api/v1/student/questions/{$single->id}/submit", ['option_id' => $this->correctOption($single)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('question');

        $student = Student::query()->where('student_no', '1411131000')->firstOrFail();
        KnowledgeCardCompletion::query()->create([
            'student_id' => $student->id,
            'course_id' => $seed['course'],
            'knowledge_card_id' => $seed['if'],
            'method' => KnowledgeCardCompletion::METHOD_QUIZ,
            'completed_at' => now(),
        ]);

        $this->withToken($token)
            ->getJson("/api/v1/student/questions/{$sameUnit->id}")
            ->assertOk()
            ->assertJsonPath('question.locked', true)
            ->assertJsonPath('question.locked_cards.0.title', 'switch');

        $this->withToken($token)
            ->postJson("/api/v1/student/questions/{$single->id}/submit", ['option_id' => $this->correctOption($single)])
            ->assertOk()
            ->assertJsonPath('system_status', 'correct');
    }

    /**
     * 第一章：單元 A（if、switch）、單元 B（for）、草稿單元（draft）；第二章：單元 C（function）
     *
     * @return array<string, int>
     */
    private function seedCourse(): array
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '題目練習課',
            'semester' => '115-1',
            'class_name' => '資應',
            'description' => '練習範圍',
        ])->assertCreated()->json('course.id');

        $chapter1 = $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$courseId}/chapters", ['name' => '第一章'])
            ->json('chapter.id');
        $chapter2 = $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$courseId}/chapters", ['name' => '第二章'])
            ->json('chapter.id');

        $unit = fn (int $chapterId, string $name, string $status = 'published') => $this->withToken($token)
            ->postJson("/api/v1/teacher/chapters/{$chapterId}/units", ['name' => $name, 'status' => $status])
            ->json('unit.id');
        $card = fn (int $unitId, string $title) => $this->withToken($token)
            ->postJson("/api/v1/teacher/units/{$unitId}/knowledge-cards", ['title' => $title, 'content' => $title])
            ->json('knowledge_card.id');

        $unitA = $unit($chapter1, '條件判斷');
        $unitB = $unit($chapter1, '迴圈');
        $draftUnit = $unit($chapter1, '草稿', 'draft');
        $unitC = $unit($chapter2, '函式');

        $seed = [
            'course' => $courseId,
            'chapter1' => $chapter1,
            'unitA' => $unitA,
            'if' => $card($unitA, 'if'),
            'switch' => $card($unitA, 'switch'),
            'for' => $card($unitB, 'for'),
            'draft' => $card($draftUnit, 'draft'),
            'function' => $card($unitC, 'function'),
        ];

        $student = Student::query()->where('student_no', '1411131000')->firstOrFail();
        Enrollment::query()->firstOrCreate(['student_id' => $student->id, 'course_id' => $courseId]);

        return $seed;
    }

    /**
     * @param  list<int>  $cardIds
     */
    private function createChoice(int $courseId, array $cardIds, string $title, string $bloomId = 'B21'): Question
    {
        $course = Course::query()->findOrFail($courseId);
        $question = Question::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $course->teacher_id,
            'title' => $title,
            'type' => Question::TYPE_CHOICE,
            'question_content' => "{$title} 題幹",
            'bloom_id' => $bloomId,
        ]);
        $question->knowledgeCards()->attach($cardIds);
        $question->options()->createMany([
            ['title' => '正確', 'is_answer' => true],
            ['title' => '錯誤', 'is_answer' => false],
        ]);

        return $question->load('options');
    }

    private function correctOption(Question $question): int
    {
        return $question->options->firstWhere('is_answer', true)->id;
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
