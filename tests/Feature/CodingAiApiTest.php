<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Question;
use App\Models\QuestionRecord;
use App\Models\Teacher;
use App\Models\Unit;
use App\Services\AI\AiProviderInterface;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class CodingAiApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_coding_execute_sandbox_runs_code_and_captures_output(): void
    {
        $response = $this->withToken($this->studentToken())
            ->postJson('/api/v1/student/coding-execute', [
                'code' => 'echo "Hello Antigravity";',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('output', 'Hello Antigravity')
            ->assertJsonPath('timeout', false);
    }

    public function test_coding_execute_with_stdin_input(): void
    {
        $code = <<<'PHP'
        $input = trim(fgets(STDIN));
        echo "Received: " . $input;
        PHP;

        $response = $this->withToken($this->studentToken())
            ->postJson('/api/v1/student/coding-execute', [
                'code' => $code,
                'input' => "456\n",
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('output', 'Received: 456');
    }

    public function test_coding_execute_syntax_error_handling(): void
    {
        $response = $this->withToken($this->studentToken())
            ->postJson('/api/v1/student/coding-execute', [
                'code' => 'echo "missing semicolon"',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('timeout', false);
    }

    public function test_student_submits_coding_question_stores_in_db_without_ai_grading(): void
    {
        // 建立 Mock AI Provider，預期學生送出作答時絕不呼叫 AI (避免消耗 Token)
        $mockAi = $this->createMock(AiProviderInterface::class);
        $mockAi->expects($this->never())
            ->method('evaluate');

        $this->app->instance(AiProviderInterface::class, $mockAi);

        $course = $this->yingCourse();
        $question = $this->makeCodingQuestion($course);

        // 學生送出作答
        $submit = $this->withToken($this->studentToken())
            ->postJson("/api/v1/student/questions/{$question->id}/submit", [
                'code' => '<?php echo "Hello World";',
            ]);

        $submit->assertOk()
            ->assertJsonPath('message', '已提交')
            ->assertJsonPath('system_status', QuestionRecord::STATUS_PENDING);

        $recordId = (int) $submit->json('record.id');

        // 驗證資料庫作答紀錄已直接存入，solo 為 null，狀態為 pending
        $this->assertDatabaseHas('question_records', [
            'id' => $recordId,
            'solo' => null,
            'system_status' => QuestionRecord::STATUS_PENDING,
            'teacher_status' => QuestionRecord::STATUS_PENDING,
        ]);

        // 驗證尚未執行 AI 批改，ai_feedback 表沒有這筆紀錄
        $this->assertDatabaseMissing('ai_feedback', [
            'question_record_id' => $recordId,
        ]);

        // 學生查詢自己的作答紀錄，確認 ai_feedback 為 null
        $recordResponse = $this->withToken($this->studentToken())
            ->getJson("/api/v1/student/question-records/{$recordId}");

        $recordResponse->assertOk()
            ->assertJsonPath('record.id', $recordId)
            ->assertJsonPath('record.solo', null)
            ->assertJsonPath('record.ai_feedback', null);

        // 接著由【教師端】手動觸發 AI 批改 (只留教師端 AI 批改)
        $teacherMockAi = $this->createMock(AiProviderInterface::class);
        $teacherMockAi->expects($this->once())
            ->method('evaluate')
            ->willReturn([
                'solo' => 'S4',
                'feedback' => '程式正確使用了迴圈與條件判斷，完整達成解題需求。',
            ]);

        $this->app->instance(AiProviderInterface::class, $teacherMockAi);

        $gradeResponse = $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/question-records/{$recordId}/grade");

        $gradeResponse->assertOk()
            ->assertJsonPath('message', 'AI 批改完成')
            ->assertJsonPath('data.solo', 4)
            ->assertJsonPath('data.feedback_content.solo', 'S4');

        // 驗證此時資料庫已更新 solo 與 ai_feedback
        $this->assertDatabaseHas('question_records', [
            'id' => $recordId,
            'solo' => 4,
        ]);

        $this->assertDatabaseHas('ai_feedback', [
            'question_record_id' => $recordId,
        ]);

        // 教師查詢該課程作答紀錄，確認教師端看到 ai_feedback
        $teacherRecords = $this->withToken($this->teacherToken())
            ->getJson("/api/v1/teacher/courses/{$course->id}/question-records");

        $teacherRecords->assertOk()
            ->assertJsonPath('records.0.id', $recordId)
            ->assertJsonPath('records.0.ai_feedback.solo', 'S4');
    }

    public function test_teacher_can_manually_trigger_ai_regrade(): void
    {
        $mockAi = $this->createMock(AiProviderInterface::class);
        $mockAi->expects($this->once())
            ->method('evaluate')
            ->willReturn([
                'solo' => 'S5',
                'feedback' => '架構優異，表現超出預期。',
            ]);

        $this->app->instance(AiProviderInterface::class, $mockAi);

        $course = $this->yingCourse();
        $question = $this->makeCodingQuestion($course);

        $record = QuestionRecord::create([
            'student_id'     => 1,
            'question_id'    => $question->id,
            'result'         => 'echo "smart code";',
            'system_status'  => QuestionRecord::STATUS_PENDING,
            'teacher_status' => QuestionRecord::STATUS_PENDING,
            'solo'           => 1,
        ]);

        $response = $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/question-records/{$record->id}/grade");

        $response->assertOk()
            ->assertJsonPath('message', 'AI 批改完成')
            ->assertJsonPath('data.solo', 5)
            ->assertJsonPath('data.feedback_content.solo', 'S5');

        $this->assertDatabaseHas('question_records', [
            'id' => $record->id,
            'solo' => 5,
        ]);
    }

    public function test_compatibility_routes_work(): void
    {
        $course = $this->yingCourse();
        $question = $this->makeCodingQuestion($course);

        // 1. POST /api/v1/coding-execute
        $this->postJson('/api/v1/coding-execute', [
            'code' => 'echo "compat ok";',
        ])->assertOk()->assertJsonPath('output', 'compat ok');

        // 2. POST /api/v1/coding-submissions (直接入庫，不自動執行 AI 批改)
        $sub = $this->postJson('/api/v1/coding-submissions', [
            'question_id' => $question->id,
            'code' => 'echo "compat test";',
        ]);

        $sub->assertStatus(201);

        $recordId = (int) $sub->json('record_id');

        // 3. GET /api/v1/coding-records/{record} - 尚未批改前為 processing
        $getRec = $this->getJson("/api/v1/coding-records/{$recordId}");
        $getRec->assertOk()
            ->assertJsonPath('completed', false)
            ->assertJsonPath('status', 'processing');

        // 4. 教師端觸發批改
        $mockAi = $this->createMock(AiProviderInterface::class);
        $mockAi->expects($this->once())
            ->method('evaluate')
            ->willReturn([
                'solo' => 'S3',
                'feedback' => '相容路由測試回饋',
            ]);
        $this->app->instance(AiProviderInterface::class, $mockAi);

        $this->withToken($this->teacherToken())
            ->postJson("/api/v1/teacher/question-records/{$recordId}/grade")
            ->assertOk();

        // 5. 批改後查詢即為 completed
        $getRecAfter = $this->getJson("/api/v1/coding-records/{$recordId}");
        $getRecAfter->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('solo', 3)
            ->assertJsonPath('feedback.solo', 'S3');
    }

    private function yingCourse(): Course
    {
        return Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
    }

    private function makeCodingQuestion(Course $course): Question
    {
        $teacher = Teacher::query()->findOrFail($course->teacher_id);
        $question = Question::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => '實作題測試',
            'type' => Question::TYPE_CODING,
            'question_content' => '實作題測試題幹',
            'bloom_id' => 'B3',
            'description' => '測試 Coding 題',
            'show_example' => false,
            'starter_code' => '$a = 10;',
            'expected_output' => '30',
            'reference_answer' => 'echo 30;',
        ]);

        $unit = $this->ensureUnit($course);
        $card = KnowledgeCard::query()->create([
            'unit_id' => $unit->id,
            'course_id' => $course->id,
            'title' => '卡片 實作題',
            'content' => '內容',
            'sort_order' => 1,
        ]);
        $question->knowledgeCards()->attach($card->id);

        return $question;
    }

    private function ensureUnit(Course $course): Unit
    {
        $chapter = $course->chapters()->first();
        if ($chapter === null) {
            $chapter = $course->chapters()->create([
                'name' => '測試章節',
                'sort_order' => 1,
            ]);
        }

        $unit = $chapter->units()->first();
        if ($unit === null) {
            $unit = $chapter->units()->create([
                'name' => '測試單元',
                'sort_order' => 1,
            ]);
        }

        return $unit;
    }

    private function studentToken(): string
    {
        return $this->loginToken('1411131000');
    }

    private function teacherToken(): string
    {
        return $this->loginToken('teacher2@school.edu.tw');
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
