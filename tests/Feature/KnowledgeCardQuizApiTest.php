<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\QuestionRecord;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class KnowledgeCardQuizApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_student_completes_card_after_every_card_question_is_answered_correctly(): void
    {
        [$courseId, $cardId, $otherCardId] = $this->seedCourse();
        $choice = $this->createChoice($courseId, [$cardId], '選擇題', 'B11');
        $trueFalse = $this->createChoice($courseId, [$cardId], '是非題', 'B11', Question::TYPE_TRUE_FALSE);
        $shared = $this->createChoice($courseId, [$cardId, $otherCardId], '跨卡是非', 'B11', Question::TYPE_TRUE_FALSE);
        $fill = $this->createFill($courseId, [$cardId]);
        $this->createChoice($courseId, [$cardId], '理解選擇題', 'B21');
        $this->createCoding($courseId, [$cardId]);

        $token = $this->loginToken('s1411131000');
        $base = "/api/v1/student/courses/{$courseId}/knowledge-cards/{$cardId}/quiz";

        $this->withToken($token)
            ->getJson($base)
            ->assertOk()
            ->assertJsonPath('has_quiz', true)
            ->assertJsonPath('completed', false)
            ->assertJsonPath('question_count', 2)
            ->assertJsonPath('questions.0.id', $choice->id)
            ->assertJsonPath('questions.0.answered', false)
            ->assertJsonPath('questions.0.knowledge_cards.0.title', 'if')
            ->assertJsonPath('questions.0.knowledge_cards.0.content', 'if 條件判斷')
            ->assertJsonPath('questions.1.id', $trueFalse->id)
            ->assertJsonMissingPath('questions.0.options.0.is_answer');

        $this->withToken($token)
            ->postJson("{$base}/questions/{$fill->id}", ['answers' => ['1' => 'echo']])
            ->assertUnprocessable();

        $this->withToken($token)
            ->postJson("{$base}/questions/{$shared->id}", ['option_id' => $this->correctOption($shared)])
            ->assertUnprocessable();

        $this->withToken($token)
            ->postJson("{$base}/questions/{$choice->id}", ['option_id' => $this->wrongOption($choice)])
            ->assertOk()
            ->assertJsonPath('system_status', 'wrong')
            ->assertJsonPath('question_status.answered', true)
            ->assertJsonPath('question_status.correct', false)
            ->assertJsonPath('completed', false);

        $this->withToken($token)
            ->postJson("{$base}/questions/{$choice->id}", ['option_id' => $this->correctOption($choice)])
            ->assertOk()
            ->assertJsonPath('correct_count', 1)
            ->assertJsonPath('completed', false);

        $this->withToken($token)
            ->postJson("{$base}/questions/{$trueFalse->id}", ['option_id' => $this->correctOption($trueFalse)])
            ->assertOk()
            ->assertJsonPath('system_status', 'correct')
            ->assertJsonPath('correct_count', 2)
            ->assertJsonPath('completed', true)
            ->assertJsonMissingPath('review');

        $this->assertSame(3, QuestionRecord::query()->where('source', QuestionRecord::SOURCE_QUIZ)->count());

        $this->withToken($this->loginToken('teacher@school.edu.tw'))
            ->getJson("/api/v1/teacher/courses/{$courseId}/question-records")
            ->assertOk()
            ->assertJsonCount(0, 'records');

        $token = $this->loginToken('s1411131000');

        $this->withToken($token)
            ->getJson("/api/v1/student/courses/{$courseId}/graph")
            ->assertOk()
            ->assertJsonPath('graph.chapters.0.units.0.knowledge_cards.0.completed', true)
            ->assertJsonPath('graph.chapters.0.units.0.knowledge_cards.0.has_quiz', true);

        // 跨卡題不進即學即測：別卡沒有自己的 B11 題，改用「我已讀完」
        $this->withToken($token)
            ->getJson("/api/v1/student/courses/{$courseId}/knowledge-cards/{$otherCardId}/quiz")
            ->assertOk()
            ->assertJsonPath('has_quiz', false)
            ->assertJsonPath('question_count', 0)
            ->assertJsonPath('completed', false);

        $this->withToken($token)
            ->getJson("/api/v1/student/courses/{$courseId}/learning-progress")
            ->assertOk()
            ->assertJsonPath('progress.total', 2)
            ->assertJsonPath('progress.completed', 1);

        $practiceIds = collect(
            $this->withToken($token)
                ->getJson("/api/v1/student/courses/{$courseId}/questions")
                ->assertOk()
                ->json('questions'),
        )->pluck('id');

        $this->assertTrue($practiceIds->contains($shared->id));
        $this->assertFalse($practiceIds->contains($trueFalse->id));
    }

    public function test_quiz_draws_a_fixed_five_questions_per_student(): void
    {
        [$courseId, $cardId] = $this->seedCourse();
        $questions = collect(range(1, 7))
            ->map(fn (int $i) => $this->createChoice($courseId, [$cardId], "第 {$i} 題", 'B11'))
            ->keyBy('id');

        $token = $this->loginToken('s1411131000');
        $base = "/api/v1/student/courses/{$courseId}/knowledge-cards/{$cardId}/quiz";

        $drawn = collect($this->withToken($token)->getJson($base)
            ->assertOk()
            ->assertJsonPath('question_count', 5)
            ->json('questions'))->pluck('id');

        $this->assertSame($drawn->all(), collect($this->withToken($token)->getJson($base)->json('questions'))->pluck('id')->all());
        $this->assertSame($drawn->sort()->values()->all(), $drawn->all());

        $notDrawn = $questions->keys()->diff($drawn)->first();
        $this->withToken($token)
            ->postJson("{$base}/questions/{$notDrawn}", ['option_id' => $this->correctOption($questions[$notDrawn])])
            ->assertUnprocessable();

        foreach ($drawn as $index => $id) {
            $this->withToken($token)
                ->postJson("{$base}/questions/{$id}", ['option_id' => $this->correctOption($questions[$id])])
                ->assertOk()
                ->assertJsonPath('total', 5)
                ->assertJsonPath('completed', $index === 4);
        }

        $this->withToken($token)
            ->getJson("/api/v1/student/courses/{$courseId}/learning-progress")
            ->assertOk()
            ->assertJsonPath('progress.chapters.0.units.0.knowledge_cards.0.status', 'completed');
    }

    public function test_learning_progress_reports_card_status_and_last_activity(): void
    {
        [$courseId, $cardId, $otherCardId] = $this->seedCourse();
        $first = $this->createChoice($courseId, [$cardId], '第一題');
        $second = $this->createChoice($courseId, [$cardId], '第二題');
        $this->createChoice($courseId, [$otherCardId], '別卡題');

        $token = $this->loginToken('s1411131000');
        $base = "/api/v1/student/courses/{$courseId}/knowledge-cards/{$cardId}/quiz";
        $progressUrl = "/api/v1/student/courses/{$courseId}/learning-progress";

        $this->withToken($token)
            ->getJson($progressUrl)
            ->assertOk()
            ->assertJsonPath('progress.summary.not_started', 2)
            ->assertJsonPath('progress.last_activity', null);

        $this->withToken($token)
            ->postJson("{$base}/questions/{$first->id}", ['option_id' => $this->correctOption($first)])
            ->assertOk();

        $this->withToken($token)
            ->getJson($progressUrl)
            ->assertOk()
            ->assertJsonPath('progress.chapters.0.units.0.knowledge_cards.0.status', 'in_progress')
            ->assertJsonPath('progress.chapters.0.units.0.knowledge_cards.0.correct_count', 1)
            ->assertJsonPath('progress.chapters.0.units.0.knowledge_cards.0.question_total', 2);

        // 全部作答過就算完成，答錯也算
        $this->withToken($token)
            ->postJson("{$base}/questions/{$second->id}", ['option_id' => $this->wrongOption($second)])
            ->assertOk()
            ->assertJsonPath('correct_count', 1)
            ->assertJsonPath('completed', true);

        $this->withToken($token)
            ->getJson($progressUrl)
            ->assertOk()
            ->assertJsonPath('progress.chapters.0.units.0.knowledge_cards.0.status', 'completed')
            ->assertJsonPath('progress.chapters.0.units.0.knowledge_cards.0.correct_count', 1)
            ->assertJsonPath('progress.summary.completed', 1)
            ->assertJsonPath('progress.summary.not_started', 1)
            ->assertJsonPath('progress.last_activity.question_id', $second->id)
            ->assertJsonPath('progress.last_activity.knowledge_card.id', $cardId);

        $this->withToken($token)
            ->postJson("/api/v1/student/questions/{$second->id}/submit", ['option_id' => $this->correctOption($second)])
            ->assertNotFound();

        $this->withToken($token)
            ->postJson("{$base}/questions/{$second->id}", ['option_id' => $this->correctOption($second)])
            ->assertOk()
            ->assertJsonPath('correct_count', 2)
            ->assertJsonPath('completed', true);
    }

    public function test_learning_analytics_uses_only_own_records(): void
    {
        [$courseId, $cardId, $otherCardId] = $this->seedCourse();
        $remember = $this->createChoice($courseId, [$cardId], '記憶題', 'B11');
        $apply = $this->createChoice($courseId, [$otherCardId], '應用題', 'B31');
        $this->createCoding($courseId, [$cardId]);

        $classmate = Student::query()->create([
            'password' => self::PASSWORD,
            'student_no' => '1411131999',
            'name' => '同學',
            'class_name' => '資應',
            'email' => Student::emailFromStudentNo('1411131999'),
        ]);
        Enrollment::query()->create(['student_id' => $classmate->id, 'course_id' => $courseId]);
        QuestionRecord::query()->create([
            'student_id' => $classmate->id,
            'question_id' => $apply->id,
            'result' => (string) $this->wrongOption($apply),
            'system_status' => QuestionRecord::STATUS_WRONG,
            'teacher_status' => QuestionRecord::STATUS_WRONG,
        ]);

        $token = $this->loginToken('s1411131000');
        $base = "/api/v1/student/courses/{$courseId}/knowledge-cards";

        $this->withToken($token)
            ->getJson("/api/v1/student/courses/{$courseId}/learning-analytics")
            ->assertOk()
            ->assertJsonPath('analytics.progress_percent', 0)
            ->assertJsonPath('analytics.bloom_radar.0.rate', null);

        $this->withToken($token)
            ->postJson("{$base}/{$cardId}/quiz/questions/{$remember->id}", ['option_id' => $this->correctOption($remember)])
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonMissingPath('review');

        // 別卡沒有 B11 題目：讀完即完成，應用題改在題目練習作答
        $this->withToken($token)
            ->postJson("{$base}/{$otherCardId}/read")
            ->assertOk();

        $this->withToken($token)
            ->postJson("/api/v1/student/questions/{$apply->id}/submit", ['option_id' => $this->correctOption($apply)])
            ->assertOk();

        $response = $this->withToken($token)
            ->getJson("/api/v1/student/courses/{$courseId}/learning-analytics")
            ->assertOk()
            ->assertJsonPath('analytics.progress_percent', 100)
            ->assertJsonMissingPath('analytics.personal')
            ->assertJsonMissingPath('analytics.bloom_radar.0.class_average')
            ->assertJsonPath('analytics.bloom_radar.0.title', '記憶')
            // 即學即測不算進雷達：記憶題只在即學即測答過
            ->assertJsonPath('analytics.bloom_radar.0.rate', null)
            ->assertJsonPath('analytics.bloom_radar.2.rate', 100)
            ->assertJsonPath('analytics.bloom_radar.2.answered_count', 1)
            ->assertJsonPath('analytics.bloom_radar.3.rate', null)
            ->assertJsonPath('analytics.activities.knowledge_cards.done', 2)
            ->assertJsonPath('analytics.activities.practice.total', 2)
            ->assertJsonPath('analytics.activities.practice.answered', 1)
            ->assertJsonPath('analytics.activities.practice.correct', 1)
            ->assertJsonPath('analytics.activities.practice.rate', 100)
            ->assertJsonPath('analytics.activities.practice.scopes.0.scope', 'card')
            ->assertJsonPath('analytics.activities.practice.scopes.0.total', 2)
            ->assertJsonPath('analytics.activities.practice.scopes.0.answered', 1)
            ->assertJsonPath('analytics.activities.practice.scopes.3.total', 0)
            ->assertJsonMissingPath('analytics.activities.coding');

        $this->assertStringNotContainsString('1411131999', $response->getContent());
        $this->assertStringNotContainsString('同學', $response->getContent());
    }

    public function test_quiz_rejects_question_not_on_card(): void
    {
        [$courseId, $cardId, $otherCardId] = $this->seedCourse();
        $this->createChoice($courseId, [$cardId], '本卡題');
        $other = $this->createChoice($courseId, [$otherCardId], '別卡題');
        $coding = $this->createCoding($courseId, [$cardId]);

        $token = $this->loginToken('s1411131000');
        $base = "/api/v1/student/courses/{$courseId}/knowledge-cards/{$cardId}/quiz";

        $this->withToken($token)
            ->postJson("{$base}/questions/{$other->id}", ['option_id' => $this->correctOption($other)])
            ->assertUnprocessable();

        $this->withToken($token)
            ->postJson("{$base}/questions/{$coding->id}", ['code' => '<?php echo 1;'])
            ->assertUnprocessable();
    }

    public function test_card_without_quiz_is_completed_by_reading(): void
    {
        [$courseId, $cardId, $plainCardId] = $this->seedCourse();
        $this->createChoice($courseId, [$cardId], '有題目');
        $this->createCoding($courseId, [$plainCardId]);

        $token = $this->loginToken('s1411131000');

        $this->withToken($token)
            ->getJson("/api/v1/student/courses/{$courseId}/knowledge-cards/{$plainCardId}/quiz")
            ->assertOk()
            ->assertJsonPath('has_quiz', false)
            ->assertJsonCount(0, 'questions');

        $this->withToken($token)
            ->postJson("/api/v1/student/courses/{$courseId}/knowledge-cards/{$plainCardId}/read")
            ->assertOk()
            ->assertJsonPath('completed', true);

        $this->withToken($token)
            ->postJson("/api/v1/student/courses/{$courseId}/knowledge-cards/{$cardId}/read")
            ->assertUnprocessable();

        $this->assertDatabaseHas('knowledge_card_completions', [
            'knowledge_card_id' => $plainCardId,
            'method' => 'read',
        ]);

        $later = $this->createChoice($courseId, [$plainCardId], '之後才掛的題目');

        $this->withToken($token)
            ->getJson("/api/v1/student/courses/{$courseId}/knowledge-cards/{$plainCardId}/quiz")
            ->assertOk()
            ->assertJsonPath('completed', false);

        $this->withToken($token)
            ->getJson("/api/v1/student/courses/{$courseId}/learning-progress")
            ->assertOk()
            ->assertJsonPath('progress.chapters.0.units.0.knowledge_cards.1.status', 'not_started');

        $this->withToken($token)
            ->postJson(
                "/api/v1/student/courses/{$courseId}/knowledge-cards/{$plainCardId}/quiz/questions/{$later->id}",
                ['option_id' => $this->correctOption($later)],
            )
            ->assertOk()
            ->assertJsonPath('completed', true);

        $this->assertDatabaseHas('knowledge_card_completions', [
            'knowledge_card_id' => $plainCardId,
            'method' => 'quiz',
        ]);
    }

    public function test_student_not_enrolled_cannot_open_quiz(): void
    {
        [$courseId, $cardId] = $this->seedCourse(enroll: false);

        $this->withToken($this->loginToken('s1411131000'))
            ->getJson("/api/v1/student/courses/{$courseId}/knowledge-cards/{$cardId}/quiz")
            ->assertNotFound();
    }

    /**
     * @return array{0: int, 1: int, 2: int} courseId, cardId, otherCardId
     */
    private function seedCourse(bool $enroll = true): array
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '即學即測課',
            'semester' => '115-1',
            'class_name' => '資應',
            'description' => '知識卡即學即測',
        ])->assertCreated()->json('course.id');

        $chapterId = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/chapters", [
            'name' => '第一章',
        ])->json('chapter.id');

        $unitId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
            'name' => '條件判斷',
            'status' => 'published',
        ])->json('unit.id');

        $cardId = $this->withToken($token)->postJson("/api/v1/teacher/units/{$unitId}/knowledge-cards", [
            'title' => 'if',
            'content' => 'if 條件判斷',
        ])->json('knowledge_card.id');

        $otherCardId = $this->withToken($token)->postJson("/api/v1/teacher/units/{$unitId}/knowledge-cards", [
            'title' => 'switch',
            'content' => 'switch 多重判斷',
        ])->json('knowledge_card.id');

        if ($enroll) {
            $student = Student::query()->where('student_no', '1411131000')->firstOrFail();
            Enrollment::query()->firstOrCreate(['student_id' => $student->id, 'course_id' => $courseId]);
        }

        return [$courseId, $cardId, $otherCardId];
    }

    /**
     * @param  list<int>  $cardIds
     */
    private function createQuestion(int $courseId, array $cardIds, string $title, string $type, string $bloomId): Question
    {
        $course = Course::query()->findOrFail($courseId);
        $question = Question::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $course->teacher_id,
            'title' => $title,
            'type' => $type,
            'question_content' => "{$title} 題幹",
            'bloom_id' => $bloomId,
        ]);
        $question->knowledgeCards()->attach($cardIds);

        return $question;
    }

    /**
     * @param  list<int>  $cardIds
     */
    private function createChoice(
        int $courseId,
        array $cardIds,
        string $title,
        string $bloomId = 'B11',
        string $type = Question::TYPE_CHOICE,
    ): Question {
        $question = $this->createQuestion($courseId, $cardIds, $title, $type, $bloomId);
        $question->options()->createMany([
            ['title' => '正確', 'is_answer' => true, 'description' => '因為正確'],
            ['title' => '錯誤', 'is_answer' => false],
        ]);

        return $question->load('options');
    }

    /**
     * @param  list<int>  $cardIds
     */
    private function createFill(int $courseId, array $cardIds): Question
    {
        $question = $this->createQuestion($courseId, $cardIds, '填空題', Question::TYPE_FILL, 'B31');
        $question->subAnswers()->create(['sub_id' => 1, 'answer' => 'echo', 'solo' => 3]);

        return $question;
    }

    /**
     * @param  list<int>  $cardIds
     */
    private function createCoding(int $courseId, array $cardIds): Question
    {
        return $this->createQuestion($courseId, $cardIds, '實作題', Question::TYPE_CODING, 'B32');
    }

    private function correctOption(Question $question): int
    {
        return $question->options->firstWhere('is_answer', true)->id;
    }

    private function wrongOption(Question $question): int
    {
        return $question->options->firstWhere('is_answer', false)->id;
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
