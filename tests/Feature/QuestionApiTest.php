<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionRecord;
use App\Models\QuestionSubAnswer;
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
            ->assertJsonMissing(['answer', 'solo']);

        $show = $this->withToken($this->studentToken())
            ->getJson("/api/v1/student/questions/{$choice->id}");

        $show->assertOk()
            ->assertJsonPath('question.type', Question::TYPE_CHOICE)
            ->assertJsonMissingPath('question.options.0.is_answer')
            ->assertJsonMissingPath('question.options.0.description')
            ->assertJsonPath('question.examples', [])
            ->assertJsonPath('question.description', '能否辨識 PHP 變數');

        $coding = Question::query()->where('course_id', $course->id)->where('type', Question::TYPE_CODING)->firstOrFail();
        $this->withToken($this->studentToken())
            ->getJson("/api/v1/student/questions/{$coding->id}")
            ->assertOk()
            ->assertJsonPath('question.examples', [])
            ->assertJsonPath('question.starter_code', '$a = 10;')
            ->assertJsonMissingPath('question.expected_output')
            ->assertJsonMissingPath('question.reference_answer');

        $shown = $this->makeCodingQuestion($course, true);
        $this->withToken($this->studentToken())
            ->getJson("/api/v1/student/questions/{$shown->id}")
            ->assertOk()
            ->assertJsonPath('question.examples.0', '$name = "PHP";');

        $debug = Question::query()->where('course_id', $course->id)->where('type', Question::TYPE_DEBUG)->firstOrFail();
        $this->withToken($this->studentToken())
            ->getJson("/api/v1/student/questions/{$debug->id}")
            ->assertOk()
            ->assertJsonPath('question.debug_error_count', 1)
            ->assertJsonMissingPath('question.sub_ids')
            ->assertJsonMissing(['answer']);
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

    public function test_fill_accepts_fullwidth_punctuation_as_halfwidth(): void
    {
        $question = $this->makeQuestion($this->yingCourse(), Question::TYPE_FILL, 'PHP結束');
        QuestionSubAnswer::query()->create([
            'question_id' => $question->id,
            'sub_id' => 1,
            'answer' => ';',
            'solo' => QuestionSubAnswer::SOLO_CORRECT,
        ]);

        $this->withToken($this->studentToken())
            ->postJson("/api/v1/student/questions/{$question->id}/submit", [
                'answers' => ['1' => '；'],
            ])
            ->assertOk()
            ->assertJsonPath('system_status', QuestionRecord::STATUS_CORRECT)
            ->assertJsonPath('record.subs.0.is_right', true);

        $this->assertDatabaseHas('question_record_subs', [
            'sub_id' => 1,
            'answer' => '；',
            'is_right' => 1,
        ]);
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
            ->assertJsonPath('description', '漏了錢字號')
            ->assertJsonPath('record.subs.0.sub_id', 3)
            ->assertJsonPath('record.subs.0.is_right', true)
            ->assertJsonMissingPath('record.solo')
            ->assertJsonMissingPath('record.subs.0.solo');

        $this->assertDatabaseHas('question_record_subs', [
            'sub_id' => 3,
            'answer' => '$name = "Ada";',
            'is_right' => 1,
        ]);
    }

    public function test_fill_parent_solo_is_wrong_partial_or_all_correct(): void
    {
        $question = $this->makeQuestion($this->yingCourse(), Question::TYPE_FILL, 'PI常數');
        QuestionSubAnswer::query()->create([
            'question_id' => $question->id,
            'sub_id' => 1,
            'answer' => 'define',
            'solo' => QuestionSubAnswer::SOLO_CORRECT,
        ]);
        QuestionSubAnswer::query()->create([
            'question_id' => $question->id,
            'sub_id' => 2,
            'answer' => 'PI',
            'solo' => QuestionSubAnswer::SOLO_CORRECT,
        ]);

        $this->withToken($this->studentToken())
            ->postJson("/api/v1/student/questions/{$question->id}/submit", [
                'answers' => ['1' => 'x', '2' => 'y'],
            ])
            ->assertOk()
            ->assertJsonMissingPath('record.solo');

        $this->assertDatabaseHas('question_records', [
            'question_id' => $question->id,
            'solo' => QuestionRecord::SOLO_WRONG,
        ]);

        $this->withToken($this->studentToken())
            ->postJson("/api/v1/student/questions/{$question->id}/submit", [
                'answers' => ['1' => 'define', '2' => 'y'],
            ])
            ->assertOk()
            ->assertJsonMissingPath('record.solo')
            ->assertJsonPath('record.result.correct', 1)
            ->assertJsonPath('record.result.total', 2);

        $this->assertDatabaseHas('question_records', [
            'question_id' => $question->id,
            'solo' => QuestionRecord::SOLO_PARTIAL,
        ]);

        $this->withToken($this->studentToken())
            ->postJson("/api/v1/student/questions/{$question->id}/submit", [
                'answers' => ['1' => 'define', '2' => 'PI'],
            ])
            ->assertOk()
            ->assertJsonMissingPath('record.solo');

        $this->assertDatabaseHas('question_records', [
            'question_id' => $question->id,
            'solo' => QuestionRecord::SOLO_ALL_CORRECT,
        ]);
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
                'solo' => QuestionRecord::SOLO_WRONG,
            ])
            ->assertOk()
            ->assertJsonPath('record.solo', QuestionRecord::SOLO_WRONG);

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
            'solo' => QuestionOption::SOLO_CORRECT,
        ]);
        QuestionOption::query()->create([
            'question_id' => $question->id,
            'title' => 'B',
            'description' => '這不是正解',
            'is_answer' => false,
            'solo' => QuestionOption::SOLO_WRONG,
        ]);

        return $question->load('options');
    }

    private function makeDebugQuestion(Course $course): Question
    {
        $question = $this->makeQuestion($course, Question::TYPE_DEBUG, '找出語法錯誤');
        QuestionSubAnswer::query()->create([
            'question_id' => $question->id,
            'sub_id' => 3,
            'answer' => '$name = "Ada";',
            'description' => '漏了錢字號',
            'solo' => QuestionSubAnswer::SOLO_CORRECT,
        ]);

        return $question->load('subAnswers');
    }

    private function makeCodingQuestion(Course $course, bool $showExample = false): Question
    {
        $question = $this->makeQuestion($course, Question::TYPE_CODING, '輸出 hello', $showExample);
        $question->update([
            'starter_code' => '$a = 10;',
            'expected_output' => '30',
            'reference_answer' => 'echo $a + $b;',
        ]);

        return $question->fresh();
    }

    private function makeQuestion(Course $course, string $type, string $title, bool $showExample = false): Question
    {
        $teacher = Teacher::query()->findOrFail($course->teacher_id);
        $question = Question::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => $title,
            'type' => $type,
            'question_content' => $title.' 的題幹',
            'bloom_id' => 'B1',
            'description' => '能否辨識 PHP 變數',
            'show_example' => $showExample,
        ]);

        $card = KnowledgeCard::query()->create([
            'unit_id' => $this->ensureUnit($course)->id,
            'title' => '卡片 '.$title,
            'content' => '內容',
            'example' => '$name = "PHP";',
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
        return $this->loginToken('1411131000');
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
