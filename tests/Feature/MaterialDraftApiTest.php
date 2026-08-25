<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Question;
use App\Models\Topic;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Tests\Support\SimpleXlsx;
use Tests\TestCase;

class MaterialDraftApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_teacher_can_import_excel_into_draft_skipping_example_rows(): void
    {
        $path = $this->xlsxPath([
            ['請參考上方範例資料的填寫方式，於下方空白列開始填寫教材內容。'],
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是用來儲存資料的容器。', '$name = "PHP";'],
            ['第一章 PHP 簡介', '資料型別', 'PHP 的資料型別包含 string。', ''],
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $response = $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($path)],
            );

        $response->assertCreated()
            ->assertJsonPath('draft.status', 'draft')
            ->assertJsonPath('draft.name', 'PHP 程式設計')
            ->assertJsonPath('draft.topics.0.name', 'PHP 基礎')
            ->assertJsonCount(1, 'draft.topics')
            ->assertJsonPath('draft.topics.0.chapters.0.units.0.name', '變數')
            ->assertJsonPath('draft.topics.0.chapters.0.units.0.knowledge_cards.0.example', '$name = "PHP";')
            ->assertJsonCount(2, 'draft.topics.0.chapters.0.units');
    }

    public function test_template_only_example_rows_are_rejected(): void
    {
        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload(public_path('templates/material_import_template.xlsx'))],
            )->assertStatus(422);
    }

    public function test_import_requires_topic(): void
    {
        $path = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', ''],
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['file' => $this->upload($path)],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('topic');
    }

    public function test_row_after_header_is_skipped_even_if_example_column_is_empty(): void
    {
        $path = $this->xlsxPath([
            ['請參考上方範例資料的填寫方式。'],
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['不該匯入的章節', '變數', '第 4 列整列不讀', ''],
            ['第一章 PHP 簡介', '變數', '變數是用來儲存資料的容器。', ''],
        ], 'PHP 程式設計', false);

        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($path)],
            )
            ->assertCreated()
            ->assertJsonCount(1, 'draft.topics')
            ->assertJsonPath('draft.topics.0.name', 'PHP 基礎');
    }

    public function test_other_teacher_cannot_import_to_course(): void
    {
        $path = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章', '變數', '內容', ''],
        ]);

        $token = $this->loginToken('teacher@school.edu.tw');
        $course = $this->yingCourse();

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($path)],
            )->assertNotFound();
    }

    public function test_teacher_can_edit_draft_and_publish_for_enrolled_student(): void
    {
        $path = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', '$name = "PHP";'],
        ]);

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $draft = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($path)],
            )->json('draft');

        $draftId = $draft['id'];
        $topicId = $draft['topics'][0]['id'];

        $updated = $this->withToken($teacherToken)->postJson(
            "/api/v1/teacher/material-drafts/{$draftId}/topics/{$topicId}/chapters",
            ['name' => '第二章 PHP 語法'],
        );

        $updated->assertCreated()
            ->assertJsonPath('draft.topics.0.chapters.1.name', '第二章 PHP 語法');

        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/material-drafts/{$draftId}/publish")
            ->assertOk()
            ->assertJsonPath('draft.status', 'published');

        $this->assertSame(1, Topic::query()->where('course_id', $course->id)->count());
        $this->assertSame(1, KnowledgeCard::query()->count());
        $this->assertSame('$name = "PHP";', KnowledgeCard::query()->value('example'));

        $studentToken = $this->loginToken('s1411131000');
        $topics = $this->withToken($studentToken)
            ->getJson("/api/v1/student/courses/{$course->id}/topics")
            ->assertOk()
            ->json('topics');

        $this->assertSame('PHP 基礎', $topics[0]['name']);

        $topicDbId = $topics[0]['id'];
        $chapters = $this->withToken($studentToken)
            ->getJson("/api/v1/student/topics/{$topicDbId}/chapters")
            ->assertOk()
            ->json('chapters');

        $units = $this->withToken($studentToken)
            ->getJson("/api/v1/student/chapters/{$chapters[0]['id']}/units")
            ->assertOk()
            ->json('units');

        $this->withToken($studentToken)
            ->getJson("/api/v1/student/units/{$units[0]['id']}/knowledge-cards")
            ->assertOk()
            ->assertJsonPath('knowledge_cards.0.content', '變數是容器。')
            ->assertJsonPath('knowledge_cards.0.example', '$name = "PHP";');
    }

    public function test_teacher_creates_new_draft_from_published_materials_then_publishes_update(): void
    {
        $path = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', ''],
        ]);

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $published = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($path)],
            )->json('draft');

        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/material-drafts/{$published['id']}/publish")
            ->assertOk();

        $this->withToken($teacherToken)
            ->putJson("/api/v1/teacher/material-drafts/{$published['id']}/topics/{$published['topics'][0]['id']}", [
                'name' => '不該成功',
            ])
            ->assertStatus(422);

        $next = $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/courses/{$course->id}/material-drafts")
            ->assertCreated()
            ->assertJsonPath('draft.status', 'draft')
            ->json('draft');

        $this->assertNotSame($published['id'], $next['id']);
        $this->assertSame('PHP 程式設計', $next['name']);
        $this->assertSame('PHP 基礎', $next['topics'][0]['name']);

        $this->withToken($teacherToken)
            ->putJson("/api/v1/teacher/material-drafts/{$next['id']}/topics/{$next['topics'][0]['id']}", [
                'name' => 'PHP 入門',
            ])
            ->assertOk()
            ->assertJsonPath('draft.topics.0.name', 'PHP 入門');

        $studentToken = $this->loginToken('s1411131000');
        $topics = $this->withToken($studentToken)
            ->getJson("/api/v1/student/courses/{$course->id}/topics")
            ->assertOk()
            ->json('topics');

        $this->assertSame('PHP 基礎', $topics[0]['name']);
        $this->assertNotEmpty($topics[0]['updated_at']);

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/material-drafts/{$next['id']}/publish")
            ->assertOk()
            ->assertJsonPath('draft.status', 'published');

        $listed = $this->withToken($teacherToken)
            ->getJson("/api/v1/teacher/courses/{$course->id}/material-drafts")
            ->assertOk()
            ->json('drafts');

        $byId = collect($listed)->keyBy('id');
        $this->assertSame('archived', $byId[$published['id']]['status']);
        $this->assertSame('published', $byId[$next['id']]['status']);

        $studentToken = $this->loginToken('s1411131000');
        $this->withToken($studentToken)
            ->getJson("/api/v1/student/courses/{$course->id}/topics")
            ->assertOk()
            ->assertJsonPath('topics.0.name', 'PHP 入門');
    }

    public function test_publishing_second_draft_archives_the_previous_published_one(): void
    {
        $first = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', ''],
        ]);
        $second = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 Java 簡介', '類別', '類別是藍圖。', ''],
        ], 'Java 程式設計');

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $firstDraft = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($first)],
            )->json('draft');

        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/material-drafts/{$firstDraft['id']}/publish")
            ->assertOk();

        $secondDraft = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'Java 基礎', 'file' => $this->upload($second)],
            )
            ->assertCreated()
            ->assertJsonPath('draft.name', 'Java 程式設計')
            ->assertJsonPath('draft.topics.0.name', 'Java 基礎')
            ->json('draft');

        $this->assertNotSame($firstDraft['id'], $secondDraft['id']);

        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/material-drafts/{$secondDraft['id']}/publish")
            ->assertOk()
            ->assertJsonPath('draft.status', 'published');

        $listed = $this->withToken($teacherToken)
            ->getJson("/api/v1/teacher/courses/{$course->id}/material-drafts")
            ->assertOk()
            ->assertJsonCount(2, 'drafts')
            ->json('drafts');

        $byId = collect($listed)->keyBy('id');
        $this->assertSame('archived', $byId[$firstDraft['id']]['status']);
        $this->assertSame('published', $byId[$secondDraft['id']]['status']);

        $this->assertSame(2, Topic::query()->where('course_id', $course->id)->count());

        $studentToken = $this->loginToken('s1411131000');
        $this->withToken($studentToken)
            ->getJson("/api/v1/student/courses/{$course->id}/topics")
            ->assertOk()
            ->assertJsonCount(2, 'topics')
            ->assertJsonPath('topics.0.name', 'Java 基礎')
            ->assertJsonPath('topics.1.name', 'PHP 基礎');
    }

    public function test_create_draft_from_published_can_copy_only_one_topic(): void
    {
        $first = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', ''],
        ]);
        $second = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 Java 簡介', '類別', '類別是藍圖。', ''],
        ], 'Java 程式設計');

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $firstDraft = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($first)],
            )->json('draft');
        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/material-drafts/{$firstDraft['id']}/publish")
            ->assertOk();

        $secondDraft = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'Java 基礎', 'file' => $this->upload($second)],
            )->json('draft');
        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/material-drafts/{$secondDraft['id']}/publish")
            ->assertOk();

        $phpTopicId = Topic::query()
            ->where('course_id', $course->id)
            ->where('name', 'PHP 基礎')
            ->value('id');

        $copied = $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/courses/{$course->id}/material-drafts", [
                'topic_id' => $phpTopicId,
            ])
            ->assertCreated()
            ->assertJsonCount(1, 'draft.topics')
            ->assertJsonPath('draft.topics.0.name', 'PHP 基礎')
            ->json('draft');

        $this->assertSame('PHP 基礎', $copied['name']);
    }

    public function test_second_excel_with_same_material_name_is_rejected(): void
    {
        $first = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', ''],
        ], 'PHP 程式設計');
        $second = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第二章 PHP 環境', '安裝', '使用 XAMPP。', ''],
        ], 'PHP 程式設計');

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $firstDraft = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($first)],
            )->json('draft');

        $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 進階', 'file' => $this->upload($second)],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('file')
            ->assertJsonFragment([
                '教材名稱「PHP 程式設計」已存在，請修改後再試',
            ]);

        $this->assertNotEmpty($firstDraft['id']);
    }

    public function test_second_excel_with_same_topic_name_but_different_material_name_is_allowed(): void
    {
        $first = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', ''],
        ], 'PHP 程式設計');
        $second = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第二章 PHP 環境', '安裝', '使用 XAMPP。', ''],
        ], 'PHP 進階');

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $firstDraft = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($first)],
            )->json('draft');

        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/material-drafts/{$firstDraft['id']}/publish")
            ->assertOk();

        $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($second)],
            )
            ->assertCreated()
            ->assertJsonPath('draft.name', 'PHP 進階')
            ->assertJsonPath('draft.topics.0.name', 'PHP 基礎');
    }

    public function test_published_material_name_can_be_reused_for_new_import(): void
    {
        $first = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', ''],
        ], 'PHP 程式設計');
        $second = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第二章 PHP 環境', '安裝', '使用 XAMPP。', ''],
        ], 'PHP 程式設計');

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $firstDraft = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($first)],
            )->json('draft');

        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/material-drafts/{$firstDraft['id']}/publish")
            ->assertOk();

        $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 環境', 'file' => $this->upload($second)],
            )
            ->assertCreated()
            ->assertJsonPath('draft.name', 'PHP 程式設計')
            ->assertJsonPath('draft.topics.0.name', 'PHP 環境');
    }

    public function test_deleting_last_draft_topic_removes_draft_and_frees_material_name(): void
    {
        $path = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', ''],
        ], 'PHP 程式設計');

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $draft = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($path)],
            )->json('draft');

        $this->withToken($teacherToken)
            ->deleteJson("/api/v1/teacher/material-drafts/{$draft['id']}/topics/{$draft['topics'][0]['id']}")
            ->assertOk();

        $this->assertDatabaseMissing('material_drafts', ['id' => $draft['id']]);

        $retry = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', ''],
        ], 'PHP 程式設計');

        $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($retry)],
            )
            ->assertCreated()
            ->assertJsonPath('draft.topics.0.name', 'PHP 基礎');
    }

    public function test_republish_keeps_knowledge_card_id_and_question_links(): void
    {
        $path = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', ''],
        ]);

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $draft = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($path)],
            )->json('draft');

        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/material-drafts/{$draft['id']}/publish")
            ->assertOk();

        $card = KnowledgeCard::query()->firstOrFail();
        $question = Question::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $course->teacher_id,
            'title' => '變數題',
            'type' => Question::TYPE_CHOICE,
            'question_content' => '題幹',
        ]);
        $question->knowledgeCards()->attach($card->id);

        $next = $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/courses/{$course->id}/material-drafts")
            ->assertCreated()
            ->json('draft');

        $cardNodeId = $next['topics'][0]['chapters'][0]['units'][0]['knowledge_cards'][0]['id'];
        $this->withToken($teacherToken)
            ->putJson("/api/v1/teacher/material-drafts/{$next['id']}/knowledge-cards/{$cardNodeId}", [
                'title' => '變數是容器。',
                'content' => '變數是容器（已改）。',
                'example' => '$x = 1;',
            ])
            ->assertOk();

        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/material-drafts/{$next['id']}/publish")
            ->assertOk();

        $this->assertSame($card->id, KnowledgeCard::query()->value('id'));
        $this->assertSame('變數是容器（已改）。', KnowledgeCard::query()->value('content'));
        $this->assertTrue($question->knowledgeCards()->whereKey($card->id)->exists());
        $this->assertSame(1, Topic::query()->where('course_id', $course->id)->count());
    }

    public function test_draft_list_returns_newest_first_with_timestamps(): void
    {
        $first = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', ''],
        ], '教材 A');
        $second = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章 Java 簡介', '類別', '類別是藍圖。', ''],
        ], '教材 B');

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($first)],
            )->assertCreated();

        $later = $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'Java 基礎', 'file' => $this->upload($second)],
            )->json('draft');

        $listed = $this->withToken($teacherToken)
            ->getJson("/api/v1/teacher/courses/{$course->id}/material-drafts")
            ->assertOk()
            ->assertJsonPath('drafts.0.id', $later['id'])
            ->json('drafts');

        $this->assertNotEmpty($listed[0]['updated_at']);
        $this->assertSame('教材 B', $listed[0]['name']);
        $this->assertSame('教材 A', $listed[1]['name']);
    }

    public function test_excel_without_material_name_uses_first_topic(): void
    {
        $path = $this->xlsxPath([
            ['chapters (章節)', 'units (單元)', 'knowledge_card (知識卡)', '範例'],
            ['第一章 PHP 簡介', '變數', '變數是容器。', '$x = 1;'],
        ], null);

        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($path)],
            )
            ->assertCreated()
            ->assertJsonPath('draft.name', 'PHP 基礎')
            ->assertJsonPath('draft.topics.0.chapters.0.units.0.knowledge_cards.0.example', '$x = 1;');
    }

    public function test_student_cannot_read_unenrolled_course_materials(): void
    {
        $course = Course::query()->where('name', '網際系統設計 (資管)')->firstOrFail();
        $token = $this->loginToken('s1411131000');

        $this->withToken($token)
            ->getJson("/api/v1/student/courses/{$course->id}/topics")
            ->assertNotFound();
    }

    public function test_student_cannot_see_unpublished_draft(): void
    {
        $path = $this->xlsxPath([
            ['chapters', 'units', 'knowledge_card', '範例'],
            ['第一章', '變數', '草稿內容', ''],
        ]);

        $teacherToken = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->withToken($teacherToken)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/api/v1/teacher/courses/{$course->id}/materials/import",
                ['topic' => 'PHP 基礎', 'file' => $this->upload($path)],
            )->assertCreated();

        $studentToken = $this->loginToken('s1411131000');
        $this->withToken($studentToken)
            ->getJson("/api/v1/student/courses/{$course->id}/topics")
            ->assertOk()
            ->assertJsonCount(0, 'topics');
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function xlsxPath(array $rows, ?string $materialName = 'PHP 程式設計', bool $insertTemplateExample = true): string
    {
        if ($materialName !== null) {
            array_unshift($rows, ['教材名稱：'.$materialName]);
        }

        if ($insertTemplateExample) {
            foreach ($rows as $index => $row) {
                $joined = implode(' ', array_map(fn ($value) => mb_strtolower(trim((string) $value)), $row));
                $hasChapters = str_contains($joined, 'chapters') || str_contains($joined, '章節');
                $hasUnits = str_contains($joined, 'units') || str_contains($joined, '單元');
                if ($hasChapters && $hasUnits) {
                    array_splice($rows, $index + 1, 0, [[
                        '第一章 PHP 簡介',
                        '變數',
                        '這是範例列，不會匯入。',
                        '範例',
                    ]]);
                    break;
                }
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        SimpleXlsx::write($path, $rows);

        return $path;
    }

    private function upload(string $path): UploadedFile
    {
        return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function yingCourse(): Course
    {
        return Course::query()->where('name', '網際系統設計 (資應)')->firstOrFail();
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
