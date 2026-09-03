<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Question;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SimpleXlsx;
use Tests\TestCase;

class MaterialImportApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_teacher_can_import_excel_as_official_material(): void
    {
        $path = $this->xlsxPath([
            ['第一章 PHP 簡介', '1', '變數與資料型態', '1', '變數宣告', 'keyword', '變數是用來儲存資料的容器。', '$name = "PHP";'],
            ['第一章 PHP 簡介', '1', '變數與資料型態', '1', 'echo', 'function', 'echo 用來輸出。', 'echo $name;'],
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $response = $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['file' => $this->upload($path)],
            );

        $response->assertCreated()
            ->assertJsonPath('course.name', '網際系統設計')
            ->assertJsonPath('course.chapters.0.name', '第一章 PHP 簡介')
            ->assertJsonPath('course.chapters.0.units.0.name', '變數與資料型態')
            ->assertJsonPath('course.chapters.0.units.0.knowledge_cards.0.title', '變數宣告')
            ->assertJsonPath('course.chapters.0.units.0.knowledge_cards.0.type', 'keyword')
            ->assertJsonPath('course.chapters.0.units.0.knowledge_cards.0.example', '$name = "PHP";')
            ->assertJsonPath('course.chapters.0.units.0.knowledge_cards.1.type', 'function')
            ->assertJsonCount(2, 'course.chapters.0.units.0.knowledge_cards');

        $this->assertSame(1, $course->chapters()->count());
        $this->assertSame(2, KnowledgeCard::query()->count());

        $studentToken = $this->loginToken('s1411131000');
        $this->withToken($studentToken)
            ->getJson("/api/v1/student/courses/{$course->id}/graph")
            ->assertOk()
            ->assertJsonPath('graph.name', '網際系統設計')
            ->assertJsonPath('graph.chapters.0.units.0.knowledge_cards.0.code_example', '$name = "PHP";');
    }

    public function test_example_rows_and_empty_template_are_rejected(): void
    {
        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['file' => $this->upload(public_path('templates/course_template.xlsx'))],
            )->assertStatus(422);
    }

    public function test_import_requires_file(): void
    {
        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post("/api/v1/teacher/courses/{$course->id}/materials/import")
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_reimport_without_overwrite_is_rejected(): void
    {
        $path = $this->xlsxPath([
            ['第一章 PHP 簡介', '1', '變數', '1', '變數宣告', 'keyword', '變數是容器。', ''],
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->import($token, $course->id, $path)->assertCreated();

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['file' => $this->upload($path)],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('overwrite');
    }

    public function test_reimport_with_overwrite_replaces_tree_but_keeps_question_cards(): void
    {
        $first = $this->xlsxPath([
            ['第一章 留下', '1', '變數', '1', '變數宣告', 'keyword', '留下的內容', ''],
            ['第二章 會刪', '2', '比較', '1', '比較運算', 'keyword', '會刪的內容', ''],
        ]);
        $second = $this->xlsxPath([
            ['第一章 留下', '1', '變數', '1', '變數宣告', 'keyword', '更新後的內容', '$x = 1;'],
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->import($token, $course->id, $first)->assertCreated();

        $removed = KnowledgeCard::query()->where('title', '比較運算')->firstOrFail();
        $kept = KnowledgeCard::query()->where('title', '變數宣告')->firstOrFail();

        $question = Question::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $course->teacher_id,
            'title' => '比較題',
            'type' => Question::TYPE_CHOICE,
            'question_content' => '題幹',
        ]);
        $question->knowledgeCards()->attach($removed->id);

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['file' => $this->upload($second), 'overwrite' => 'true'],
            )
            ->assertCreated()
            ->assertJsonCount(1, 'course.chapters')
            ->assertJsonPath('course.chapters.0.units.0.knowledge_cards.0.content', '更新後的內容')
            ->assertJsonPath('course.chapters.0.units.0.knowledge_cards.0.id', $kept->id);

        $this->assertDatabaseHas('knowledge_cards', [
            'id' => $removed->id,
            'unit_id' => null,
        ]);
        $this->assertTrue($question->knowledgeCards()->whereKey($removed->id)->exists());
        $this->assertSame('$x = 1;', KnowledgeCard::query()->whereKey($kept->id)->value('example'));
    }

    public function test_same_card_can_attach_to_multiple_units(): void
    {
        $path = $this->xlsxPath([
            ['第一章 PHP 簡介', '1', '變數', '1', '變數宣告', 'keyword', '變數是容器。', ''],
            ['第一章 PHP 簡介', '1', '輸出', '2', '變數宣告', 'keyword', '變數是容器。', ''],
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->import($token, $course->id, $path)
            ->assertCreated()
            ->assertJsonPath('course.chapters.0.units.0.knowledge_cards.0.title', '變數宣告')
            ->assertJsonPath('course.chapters.0.units.1.knowledge_cards.0.title', '變數宣告');

        $this->assertSame(1, KnowledgeCard::query()->count());
        $this->assertSame(2, KnowledgeCard::query()->first()->units()->count());
    }

    public function test_teacher_tree_and_student_graph_require_access(): void
    {
        $path = $this->xlsxPath([
            ['第一章 PHP 簡介', '1', '變數', '1', '變數宣告', 'keyword', '變數是容器。', ''],
        ]);

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();
        $this->import($teacherToken, $course->id, $path)->assertCreated();

        $this->withToken($teacherToken)
            ->getJson("/api/v1/teacher/courses/{$course->id}/tree")
            ->assertOk()
            ->assertJsonPath('course.name', '網際系統設計')
            ->assertJsonPath('course.chapters.0.name', '第一章 PHP 簡介');

        $otherTeacher = $this->loginToken('teacher@school.edu.tw');
        $this->withToken($otherTeacher)
            ->getJson("/api/v1/teacher/courses/{$course->id}/tree")
            ->assertNotFound();

        $unenrolled = $this->loginToken('1411131000');
        $otherCourse = Course::query()->where('name', '網際系統設計')->where('class_name', '資管')->firstOrFail();
        $this->withToken($unenrolled)
            ->getJson("/api/v1/student/courses/{$otherCourse->id}/graph")
            ->assertNotFound();
    }

    public function test_other_teacher_cannot_import_to_course(): void
    {
        $path = $this->xlsxPath([
            ['第一章', '1', '變數', '1', '變數宣告', 'keyword', '內容', ''],
        ]);

        $token = $this->loginToken('teacher@school.edu.tw');
        $course = $this->yingCourse();

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['file' => $this->upload($path)],
            )->assertNotFound();
    }

    public function test_teacher_can_upload_editor_image(): void
    {
        Storage::fake('public');
        $token = $this->loginToken('teacher2@school.edu.tw');
        $file = UploadedFile::fake()->image('card.png', 20, 20);

        $response = $this->withToken($token)
            ->post('/api/v1/teacher/upload-image', ['image' => $file], ['Accept' => 'application/json']);

        $response
            ->assertCreated()
            ->assertJsonStructure(['url']);

        $this->assertIsString($response->json('url'));
        $this->assertStringContainsString('/storage/editor_images/', $response->json('url'));
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function xlsxPath(array $rows): string
    {
        $all = array_merge([
            ['chapter_title', 'chapter_order', 'unit_title', 'unit_order', 'card_name', 'card_type', 'card_content', 'code_example'],
            ['ex：第一章 PHP 基礎', 'ex：1', 'ex：變數與資料型態', 'ex：1', 'ex：變數宣告', 'ex：keyword', 'ex：示範不匯入', 'ex：code'],
        ], $rows);

        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        SimpleXlsx::write($path, $all);

        return $path;
    }

    private function import(string $token, int $courseId, string $path)
    {
        return $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$courseId}/materials/import",
                ['file' => $this->upload($path)],
            );
    }

    private function upload(string $path): UploadedFile
    {
        return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function yingCourse(): Course
    {
        return Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
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
