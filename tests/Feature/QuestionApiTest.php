<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\DebugSubInfo;
use App\Models\KnowledgeCard;
use App\Models\Question;
use App\Models\QuestionBloomSoloMapping;
use App\Models\QuestionOption;
use App\Models\QuestionRecord;
use App\Models\Teacher;
use App\Models\Unit;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class QuestionApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_student_can_get_course_questions_without_answers(): void
    {
        $course = $this->yingCourse();
        $choice = $this->makeChoiceQuestion($course);
        $this->makeDebugQuestion($course);
        $this->makeCodingQuestion($course);

        $response = $this->withToken($this->studentToken())
            ->getJson("/api/v1/student/courses/{$course->id}/questions");

        $response->assertOk()
            ->assertJsonCount(3, 'questions')
            ->assertJsonMissingPath('questions.0.options.0.is_answer')
            ->assertJsonMissing(['code_line', 'ref_answer', 'ref_output']);

        $show = $this->withToken($this->studentToken())
            ->getJson("/api/v1/student/questions/{$choice->id}");

        $show->assertOk()
            ->assertJsonPath('question.type', Question::TYPE_CHOICE)
            ->assertJsonMissingPath('question.options.0.is_answer');
    }

    public function test_unenrolled_student_cannot_get_questions(): void
    {
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資管')->firstOrFail();
        $this->makeChoiceQuestion($course);

        $this->withToken($this->studentToken())
            ->getJson("/api/v1/student/courses/{$course->id}/questions")
            ->assertNotFound();
    }

    public function test_student_can_submit_choice_question(): void
    {
        $question = $this->makeChoiceQuestion($this->yingCourse());
        $correct = $question->options()->where('is_answer', true)->firstOrFail();
        $wrong = $question->options()->where('is_answer', false)->firstOrFail();

        $this->withToken($this->studentToken())
            ->postJson("/api/v1/student/questions/{$question->id}/submit", [
                'option_id' => $wrong->id,
            ])
            ->assertOk()
            ->assertJsonPath('system_status', QuestionRecord::STATUS_WRONG)
            ->assertJsonPath('record.teacher_status', QuestionRecord::STATUS_PENDING);

        $this->withToken($this->studentToken())
            ->postJson("/api/v1/student/questions/{$question->id}/submit", [
                'option_id' => $correct->id,
            ])
            ->assertOk()
            ->assertJsonPath('system_status', QuestionRecord::STATUS_CORRECT)
            ->assertJsonPath('explanation', '變數用來存放資料');

        $this->assertSame(2, QuestionRecord::query()->where('question_id', $question->id)->count());
    }

    public function test_student_can_submit_debug_question(): void
    {
        $question = $this->makeDebugQuestion($this->yingCourse());

        $this->withToken($this->studentToken())
            ->postJson("/api/v1/student/questions/{$question->id}/submit", [
                'code_line' => 3,
                'answer' => '$name = "Ada";',
            ])
            ->assertOk()
            ->assertJsonPath('system_status', QuestionRecord::STATUS_CORRECT)
            ->assertJsonPath('description', '漏了錢字號');
    }

    public function test_student_can_submit_coding_question_as_pending(): void
    {
        $question = $this->makeCodingQuestion($this->yingCourse());

        $this->withToken($this->studentToken())
            ->postJson("/api/v1/student/questions/{$question->id}/submit", [
                'code' => '<?php echo "hi";',
            ])
            ->assertOk()
            ->assertJsonPath('message', '已提交')
            ->assertJsonPath('system_status', QuestionRecord::STATUS_PENDING)
            ->assertJsonPath('record.teacher_status', QuestionRecord::STATUS_PENDING)
            ->assertJsonPath('record.result', '<?php echo "hi";');
    }

    public function test_teacher_can_list_and_review_records(): void
    {
        $course = $this->yingCourse();
        $question = $this->makeChoiceQuestion($course);
        $correct = $question->options()->where('is_answer', true)->firstOrFail();

        $submit = $this->withToken($this->studentToken())
            ->postJson("/api/v1/student/questions/{$question->id}/submit", [
                'option_id' => $correct->id,
            ]);
        $recordId = $submit->json('record.id');

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($teacherToken)
            ->getJson("/api/v1/teacher/courses/{$course->id}/question-records")
            ->assertOk()
            ->assertJsonPath('records.0.result', (string) $correct->id)
            ->assertJsonPath('records.0.system_status', QuestionRecord::STATUS_CORRECT);

        $this->withToken($teacherToken)
            ->putJson("/api/v1/teacher/question-records/{$recordId}", [
                'teacher_status' => QuestionRecord::STATUS_WRONG,
            ])
            ->assertOk()
            ->assertJsonPath('record.teacher_status', QuestionRecord::STATUS_WRONG);

        $this->withToken($this->loginToken('teacher@school.edu.tw'))
            ->getJson("/api/v1/teacher/courses/{$course->id}/question-records")
            ->assertNotFound();
    }

    public function test_teacher_cannot_submit_student_question(): void
    {
        $question = $this->makeChoiceQuestion($this->yingCourse());

        $this->withToken($this->loginToken('teacher2@school.edu.tw'))
            ->postJson("/api/v1/student/questions/{$question->id}/submit", [
                'option_id' => 1,
            ])
            ->assertForbidden();
    }

    private function yingCourse(): Course
    {
        return Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
    }

    private function makeChoiceQuestion(Course $course): Question
    {
        $question = $this->makeQuestion($course, Question::TYPE_CHOICE, 'PHP 變數');
        QuestionOption::query()->create([
            'question_id' => $question->id,
            'title' => 'A',
            'description' => '變數用來存放資料',
            'is_answer' => true,
        ]);
        QuestionOption::query()->create([
            'question_id' => $question->id,
            'title' => 'B',
            'description' => '這不是正解',
            'is_answer' => false,
        ]);

        return $question->load('options');
    }

    private function makeDebugQuestion(Course $course): Question
    {
        $question = $this->makeQuestion($course, Question::TYPE_DEBUG, '找出語法錯誤');
        DebugSubInfo::query()->create([
            'question_id' => $question->id,
            'code_line' => 3,
            'answer' => '$name = "Ada";',
            'description' => '漏了錢字號',
        ]);

        return $question->load('debugSubInfo');
    }

    private function makeCodingQuestion(Course $course): Question
    {
        $question = $this->makeQuestion($course, Question::TYPE_CODING, '輸出 hello');
        $question->codingSubInfo()->create([
            'ref_answer' => 'echo "hello";',
            'ref_output' => 'hello',
        ]);

        return $question;
    }

    private function makeQuestion(Course $course, string $type, string $title): Question
    {
        $teacher = Teacher::query()->findOrFail($course->teacher_id);
        $question = Question::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => $title,
            'type' => $type,
            'question_content' => $title.' 的題幹',
        ]);

        QuestionBloomSoloMapping::query()->create([
            'question_id' => $question->id,
            'bloom_id' => 'B1',
            'solo_id' => 'S2',
        ]);

        $card = KnowledgeCard::query()->create([
            'unit_id' => $this->ensureUnit($course)->id,
            'title' => '卡片 '.$title,
            'content' => '內容',
            'sort_order' => 1,
        ]);
        $question->knowledgeCards()->attach($card->id);

        return $question;
    }

    private function ensureUnit(Course $course): Unit
    {
        $topic = $course->topics()->first();
        if ($topic === null) {
            $topic = $course->topics()->create([
                'name' => '測試主題',
                'sort_order' => 1,
            ]);
        }

        $chapter = $topic->chapters()->first();
        if ($chapter === null) {
            $chapter = $topic->chapters()->create([
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
        return $this->loginToken('s1411131000');
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
