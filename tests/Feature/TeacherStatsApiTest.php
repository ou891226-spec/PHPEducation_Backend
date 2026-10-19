<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Question;
use App\Models\QuestionRecord;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class TeacherStatsApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_bloom_stats_count_questions_per_unit(): void
    {
        $seed = $this->seedCourse();

        $this->withToken($seed['token'])
            ->getJson("/api/v1/teacher/courses/{$seed['course']}/stats/bloom")
            ->assertOk()
            ->assertJsonCount(12, 'columns')
            ->assertJsonPath('columns.0', 'B11')
            ->assertJsonPath('columns.11', 'B43')
            ->assertJsonCount(2, 'rows')
            ->assertJsonPath('rows.0.unit_name', '條件判斷')
            ->assertJsonPath('rows.0.chapter_name', '第一章')
            ->assertJsonPath('rows.0.counts.B11', 1)
            ->assertJsonPath('rows.0.counts.B12', 1)
            ->assertJsonPath('rows.0.counts.B43', 1)
            ->assertJsonPath('rows.0.total', 3)
            ->assertJsonPath('rows.1.counts.B12', 1)
            ->assertJsonPath('rows.1.counts.B23', 1)
            ->assertJsonPath('rows.1.total', 2)
            // 跨單元的題目合計只算一次
            ->assertJsonPath('totals.counts.B12', 1)
            ->assertJsonPath('totals.total', 4);
    }

    public function test_question_solo_stats_use_highest_annotation(): void
    {
        $seed = $this->seedCourse();

        $this->withToken($seed['token'])
            ->getJson("/api/v1/teacher/courses/{$seed['course']}/stats/solo")
            ->assertOk()
            ->assertJsonPath('columns', ['S1', 'S2', 'S3', 'S4', 'S5'])
            ->assertJsonPath('rows.0.counts.S2', 1)
            ->assertJsonPath('rows.0.counts.S3', 1)
            ->assertJsonPath('rows.0.total', 2)
            ->assertJsonPath('rows.1.counts.S3', 2)
            ->assertJsonPath('totals.counts.S2', 1)
            ->assertJsonPath('totals.counts.S3', 2)
            // 實作題沒有標註，不列入
            ->assertJsonPath('totals.total', 3);
    }

    public function test_student_solo_stats_for_one_student_and_whole_class(): void
    {
        $seed = $this->seedCourse();
        $student = Student::query()->where('student_no', '1411131000')->firstOrFail();
        $classmate = $this->createStudent('1411131998', '同學');
        Enrollment::query()->create(['student_id' => $classmate->id, 'course_id' => $seed['course']]);
        $outsider = $this->createStudent('1411131997', '外班');

        $this->record($student, $seed['crossUnit'], 3);
        $this->record($student, $seed['fill'], 2);
        $this->record($student, $seed['remember'], 2, QuestionRecord::SOURCE_QUIZ);
        $this->record($classmate, $seed['crossUnit'], 1);
        $this->record($classmate, $seed['coding'], null);
        $this->record($outsider, $seed['fill'], 3);

        $base = "/api/v1/teacher/courses/{$seed['course']}/stats/student-solo";

        $this->withToken($seed['token'])
            ->getJson("{$base}?student_id={$student->id}")
            ->assertOk()
            ->assertJsonPath('student.student_no', '1411131000')
            ->assertJsonCount(2, 'students')
            ->assertJsonPath('rows.0.counts.S3', 1)
            ->assertJsonPath('rows.0.total', 1)
            ->assertJsonPath('rows.1.counts.S2', 1)
            ->assertJsonPath('rows.1.counts.S3', 1)
            ->assertJsonPath('totals.total', 2);

        $this->withToken($seed['token'])
            ->getJson($base)
            ->assertOk()
            ->assertJsonPath('student', null)
            ->assertJsonPath('rows.0.counts.S1', 1)
            ->assertJsonPath('rows.0.counts.S3', 1)
            ->assertJsonPath('rows.1.total', 3)
            ->assertJsonPath('totals.counts.S1', 1)
            ->assertJsonPath('totals.counts.S2', 1)
            ->assertJsonPath('totals.counts.S3', 1)
            ->assertJsonPath('totals.total', 3);

        $this->withToken($seed['token'])
            ->getJson("{$base}?student_id={$outsider->id}")
            ->assertNotFound();
    }

    public function test_other_teacher_cannot_view_stats(): void
    {
        $seed = $this->seedCourse();
        $token = $this->loginToken('teacher2@school.edu.tw');

        foreach (['bloom', 'solo', 'student-solo'] as $path) {
            $this->withToken($token)
                ->getJson("/api/v1/teacher/courses/{$seed['course']}/stats/{$path}")
                ->assertNotFound();
        }
    }

    /**
     * 第一章：單元「條件判斷」（if、switch）、單元「迴圈」（for）。
     *
     * @return array<string, mixed>
     */
    private function seedCourse(): array
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '統計課',
            'semester' => '115-1',
            'class_name' => '資應',
            'description' => '統計',
        ])->assertCreated()->json('course.id');

        $chapterId = $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$courseId}/chapters", ['name' => '第一章'])
            ->json('chapter.id');
        $unit = fn (string $name) => $this->withToken($token)
            ->postJson("/api/v1/teacher/chapters/{$chapterId}/units", ['name' => $name, 'status' => 'published'])
            ->json('unit.id');
        $card = fn (int $unitId, string $title) => $this->withToken($token)
            ->postJson("/api/v1/teacher/units/{$unitId}/knowledge-cards", ['title' => $title, 'content' => $title])
            ->json('knowledge_card.id');

        $unitA = $unit('條件判斷');
        $unitB = $unit('迴圈');
        $if = $card($unitA, 'if');
        $switch = $card($unitA, 'switch');
        $for = $card($unitB, 'for');

        $create = fn (array $payload) => $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$courseId}/questions", [
                'question_content' => '題幹',
                ...$payload,
            ])
            ->assertCreated()
            ->json('question.id');
        $options = [
            ['title' => '對', 'is_answer' => true],
            ['title' => '錯', 'is_answer' => false],
        ];

        $seed = [
            'token' => $token,
            'course' => $courseId,
            'remember' => $create(['title' => '記憶', 'type' => Question::TYPE_CHOICE, 'bloom_id' => 'B11', 'knowledge_card_ids' => [$if], 'options' => $options]),
            'crossUnit' => $create(['title' => '跨單元', 'type' => Question::TYPE_CHOICE, 'bloom_id' => 'B12', 'knowledge_card_ids' => [$if, $for], 'options' => $options]),
            'fill' => $create([
                'title' => '填空',
                'type' => Question::TYPE_FILL,
                'bloom_id' => 'B23',
                'knowledge_card_ids' => [$for],
                'sub_answers' => [
                    ['sub_id' => 1, 'answer' => 'for', 'solo' => 2],
                    ['sub_id' => 2, 'answer' => '$i++', 'solo' => 3],
                ],
            ]),
            'coding' => $create(['title' => '實作', 'type' => Question::TYPE_CODING, 'bloom_id' => 'B43', 'knowledge_card_ids' => [$switch]]),
        ];

        $student = Student::query()->where('student_no', '1411131000')->firstOrFail();
        Enrollment::query()->firstOrCreate(['student_id' => $student->id, 'course_id' => $courseId]);

        return $seed;
    }

    private function createStudent(string $studentNo, string $name): Student
    {
        return Student::query()->create([
            'password' => self::PASSWORD,
            'student_no' => $studentNo,
            'name' => $name,
            'class_name' => '資應',
            'email' => Student::emailFromStudentNo($studentNo),
        ]);
    }

    private function record(Student $student, int $questionId, ?int $solo, string $source = QuestionRecord::SOURCE_PRACTICE): void
    {
        $status = match (true) {
            $solo === null => QuestionRecord::STATUS_PENDING,
            $solo > 1 => QuestionRecord::STATUS_CORRECT,
            default => QuestionRecord::STATUS_WRONG,
        };

        QuestionRecord::query()->create([
            'student_id' => $student->id,
            'question_id' => $questionId,
            'source' => $source,
            'result' => 'x',
            'system_status' => $status,
            'teacher_status' => $status,
            'solo' => $solo,
        ]);
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
