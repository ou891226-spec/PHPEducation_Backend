<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\KnowledgeCard;
use App\Models\Question;
use App\Models\Unit;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Support\SimpleXlsx;
use Tests\TestCase;

class MaterialImportModeApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_append_adds_chapters_after_existing_without_touching_old_material(): void
    {
        [$token, $course] = $this->seedTwoChapters();
        $oldCard = KnowledgeCard::query()->where('title', '變數宣告')->firstOrFail();
        $oldUnitIds = Unit::query()->pluck('id')->all();

        $this->send($token, $course->id, 'import', $this->xlsxPath([
            ['第三章 函式', '1', '函式定義', '1', 'function', 'keyword', '函式是可重複使用的程式區塊。', ''],
            ['第四章 陣列', '2', '陣列基礎', '1', 'array', 'keyword', '陣列可以存放多個值。', ''],
        ]), ['mode' => 'append'])
            ->assertCreated()
            ->assertJsonCount(4, 'course.chapters')
            ->assertJsonPath('course.chapters.2.name', '第三章 函式')
            ->assertJsonPath('course.chapters.2.sort_order', 3)
            ->assertJsonPath('course.chapters.3.sort_order', 4)
            ->assertJsonPath('course.chapters.2.units.0.status', 'published')
            ->assertJsonPath('summary.chapters', 2)
            ->assertJsonPath('summary.deleted_cards', 0);

        $oldCard->refresh();
        $this->assertSame('變數是容器。', $oldCard->content);
        $this->assertSame([], array_diff($oldUnitIds, Unit::query()->pluck('id')->all()));
    }

    public function test_append_reuses_identical_card_and_flags_same_title_with_different_content(): void
    {
        [$token, $course] = $this->seedTwoChapters();
        $original = KnowledgeCard::query()->where('title', '變數宣告')->firstOrFail();

        $path = $this->xlsxPath([
            ['第三章 複習', '1', '複習一', '1', '變數宣告', 'keyword', '  變數是容器。 ', ''],
            ['第三章 複習', '1', '複習一', '1', '比較運算', 'keyword', '完全不同的說明', ''],
        ]);

        $this->send($token, $course->id, 'preview', $path, ['mode' => 'append'])
            ->assertOk()
            ->assertJsonPath('summary.reused_cards', 1)
            ->assertJsonPath('summary.new_cards', 1)
            ->assertJsonPath('summary.possible_duplicates', 1)
            ->assertJsonPath("highlights.card:{$original->id}", 'reuse');

        $this->send($token, $course->id, 'import', $path, ['mode' => 'append'])->assertCreated();

        $this->assertSame(1, KnowledgeCard::query()->where('title', '變數宣告')->count());
        $this->assertSame(2, KnowledgeCard::query()->where('title', '比較運算')->count());
        $this->assertSame(2, $original->units()->count());
    }

    public function test_append_preview_warns_when_chapter_name_already_exists(): void
    {
        [$token, $course] = $this->seedTwoChapters();

        $this->send($token, $course->id, 'preview', $this->xlsxPath([
            ['第一章 變數', '1', '新單元', '1', '新卡', 'keyword', '新內容', ''],
        ]), ['mode' => 'append'])
            ->assertOk()
            ->assertJsonPath('duplicate_chapter_names.0', '第一章 變數');
    }

    public function test_replace_rebuilds_only_target_chapter_and_protects_linked_cards(): void
    {
        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();
        $this->send($token, $course->id, 'import', $this->xlsxPath([
            ['第一章 變數', '1', '變數', '1', '變數宣告', 'keyword', '舊內容', ''],
            ['第一章 變數', '1', '變數', '1', '會刪的卡', 'keyword', '沒人用', ''],
            ['第一章 變數', '1', '變數', '1', '題目用的卡', 'keyword', '有題目', ''],
            ['第一章 變數', '1', '型別', '2', '共用卡', 'keyword', '兩章共用', ''],
            ['第二章 運算', '2', '運算子', '1', '共用卡', 'keyword', '兩章共用', ''],
        ]))->assertCreated();

        $target = Chapter::query()->where('name', '第一章 變數')->firstOrFail();
        $other = Chapter::query()->where('name', '第二章 運算')->firstOrFail();
        $otherUnitIds = $other->units()->pluck('id')->all();
        $kept = KnowledgeCard::query()->where('title', '變數宣告')->firstOrFail();
        $unused = KnowledgeCard::query()->where('title', '會刪的卡')->firstOrFail();
        $linked = KnowledgeCard::query()->where('title', '題目用的卡')->firstOrFail();
        $shared = KnowledgeCard::query()->where('title', '共用卡')->firstOrFail();
        $question = $this->questionFor($course, $linked);

        $path = $this->xlsxPath([
            ['第一章 變數（改版）', '1', '變數', '1', '變數宣告', 'keyword', '新內容', '$x = 1;'],
            ['第一章 變數（改版）', '1', '常數', '2', '常數', 'keyword', '不能改的值', ''],
        ]);

        $this->send($token, $course->id, 'preview', $path, ['mode' => 'replace', 'chapter_id' => $target->id])
            ->assertOk()
            ->assertJsonPath('summary.deleted_cards', 1)
            ->assertJsonPath('summary.detached_cards', 2)
            ->assertJsonPath('summary.updated_cards', 1)
            ->assertJsonPath('removed.units.0', '型別')
            ->assertJsonPath("highlights.chapter:{$target->id}", 'replace')
            ->assertJsonPath("highlights.card:{$kept->id}", 'replace');

        $this->send($token, $course->id, 'import', $path, ['mode' => 'replace', 'chapter_id' => $target->id])
            ->assertCreated()
            ->assertJsonCount(2, 'course.chapters')
            ->assertJsonPath('course.chapters.0.id', $target->id)
            ->assertJsonPath('course.chapters.0.name', '第一章 變數（改版）')
            ->assertJsonPath('course.chapters.0.units.0.knowledge_cards.0.id', $kept->id)
            ->assertJsonPath('course.chapters.0.units.0.knowledge_cards.0.content', '新內容');

        $this->assertSame(1, $target->fresh()->sort_order);
        $this->assertSame($otherUnitIds, $other->units()->pluck('id')->all());
        $this->assertDatabaseMissing('knowledge_cards', ['id' => $unused->id]);

        $this->assertDatabaseHas('knowledge_cards', ['id' => $linked->id, 'unit_id' => null]);
        $this->assertTrue($question->knowledgeCards()->whereKey($linked->id)->exists());

        $shared->refresh();
        $this->assertSame($otherUnitIds, $shared->units()->pluck('units.id')->all());
        $this->assertSame($otherUnitIds[0], $shared->unit_id);
    }

    public function test_replace_validates_chapter_and_single_chapter_file(): void
    {
        [$token, $course] = $this->seedTwoChapters();
        $chapter = Chapter::query()->where('course_id', $course->id)->firstOrFail();
        $foreign = Chapter::query()->create([
            'course_id' => Course::query()->whereKeyNot($course->id)->value('id'),
            'name' => '別的課',
            'sort_order' => 1,
        ]);
        $single = $this->xlsxPath([['第一章', '1', '單元', '1', '卡', 'keyword', '內容', '']]);

        $this->send($token, $course->id, 'import', $single, ['mode' => 'replace'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('chapter_id');

        $this->send($token, $course->id, 'import', $single, ['mode' => 'replace', 'chapter_id' => $foreign->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('chapter_id');

        $this->send($token, $course->id, 'import', $this->xlsxPath([
            ['第一章', '1', '單元', '1', '卡', 'keyword', '內容', ''],
            ['第二章', '2', '單元', '1', '卡二', 'keyword', '內容', ''],
        ]), ['mode' => 'replace', 'chapter_id' => $chapter->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertSame('別的課', $foreign->fresh()->name);
    }

    public function test_overwrite_preview_lists_questions_that_lose_all_cards(): void
    {
        [$token, $course] = $this->seedTwoChapters();
        $card = KnowledgeCard::query()->where('title', '比較運算')->firstOrFail();
        $question = $this->questionFor($course, $card);

        $path = $this->xlsxPath([['第一章 變數', '1', '變數', '1', '變數宣告', 'keyword', '變數是容器。', '']]);

        $this->send($token, $course->id, 'preview', $path, ['mode' => 'overwrite'])
            ->assertOk()
            ->assertJsonPath('summary.deleted_cards', 1)
            ->assertJsonPath('summary.questions_without_cards', 1)
            ->assertJsonPath('affected_questions.0.id', $question->id)
            ->assertJsonPath('affected_questions.0.remaining_cards', 0);

        $this->send($token, $course->id, 'import', $path, ['mode' => 'overwrite'])->assertCreated();

        $this->assertDatabaseMissing('knowledge_cards', ['id' => $card->id]);
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    public function test_preview_does_not_write_to_database(): void
    {
        [$token, $course] = $this->seedTwoChapters();
        $chapter = Chapter::query()->where('course_id', $course->id)->firstOrFail();
        $before = $this->tableCounts();
        $path = $this->xlsxPath([['新章', '1', '新單元', '1', '新卡', 'keyword', '新內容', '']]);

        foreach ([
            ['mode' => 'append'],
            ['mode' => 'replace', 'chapter_id' => $chapter->id],
            ['mode' => 'overwrite'],
        ] as $params) {
            $this->send($token, $course->id, 'preview', $path, $params)
                ->assertOk()
                ->assertJsonStructure(['mode', 'fingerprint', 'summary', 'course' => ['chapters'], 'highlights', 'removed', 'affected_questions']);
        }

        $this->assertSame($before, $this->tableCounts());
    }

    public function test_import_rejects_stale_fingerprint(): void
    {
        [$token, $course] = $this->seedTwoChapters();
        $path = $this->xlsxPath([['第三章', '1', '單元', '1', '卡', 'keyword', '內容', '']]);

        $fingerprint = $this->send($token, $course->id, 'preview', $path, ['mode' => 'append'])->json('fingerprint');

        KnowledgeCard::query()->where('title', '變數宣告')->update(['content' => '別的分頁改過']);
        $before = $this->tableCounts();

        $this->send($token, $course->id, 'import', $path, ['mode' => 'append', 'fingerprint' => $fingerprint])
            ->assertStatus(409);
        $this->assertSame($before, $this->tableCounts());

        $fresh = $this->send($token, $course->id, 'preview', $path, ['mode' => 'append'])->json('fingerprint');
        $this->send($token, $course->id, 'import', $path, ['mode' => 'append', 'fingerprint' => $fresh])
            ->assertCreated();
    }

    public function test_existing_material_requires_import_mode(): void
    {
        [$token, $course] = $this->seedTwoChapters();

        $this->send($token, $course->id, 'import', $this->xlsxPath([
            ['第三章', '1', '單元', '1', '卡', 'keyword', '內容', ''],
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('mode');

        $this->send($token, $course->id, 'import', $this->xlsxPath([
            ['第三章', '1', '單元', '1', '卡', 'keyword', '內容', ''],
        ]), ['mode' => 'bogus'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('mode');
    }

    public function test_too_long_field_reports_row_number(): void
    {
        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $response = $this->send($token, $course->id, 'import', $this->xlsxPath([
            ['第一章', '1', '單元', '1', '正常卡', 'keyword', '內容', ''],
            ['第一章', '1', '單元', '1', str_repeat('長', 256), 'keyword', '內容', ''],
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('file');
        $this->assertStringContainsString('第 4 列', $response->json('errors.file.0'));
        $this->assertSame(0, KnowledgeCard::query()->count());
    }

    public function test_more_than_max_rows_is_rejected(): void
    {
        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $rows = [];
        for ($i = 1; $i <= 5001; $i++) {
            $rows[] = ['第一章', '1', '單元', '1', "卡{$i}", 'keyword', '內容', ''];
        }

        $response = $this->send($token, $course->id, 'preview', $this->xlsxPath($rows), ['mode' => 'overwrite']);

        $response->assertStatus(422)->assertJsonValidationErrors('file');
        $this->assertStringContainsString('5001', $response->json('errors.file.0'));
    }

    public function test_other_teacher_cannot_preview(): void
    {
        [, $course] = $this->seedTwoChapters();
        $otherToken = $this->loginToken('teacher@school.edu.tw');

        $this->send($otherToken, $course->id, 'preview', $this->xlsxPath([
            ['第一章', '1', '單元', '1', '卡', 'keyword', '內容', ''],
        ]), ['mode' => 'append'])->assertNotFound();
    }

    /**
     * @return array{0: string, 1: Course}
     */
    private function seedTwoChapters(): array
    {
        $token = $this->loginToken('teacher2@school.edu.tw');
        $course = $this->yingCourse();

        $this->send($token, $course->id, 'import', $this->xlsxPath([
            ['第一章 變數', '1', '變數', '1', '變數宣告', 'keyword', '變數是容器。', ''],
            ['第二章 運算', '2', '比較', '1', '比較運算', 'keyword', '比較兩個值。', ''],
        ]))->assertCreated();

        return [$token, $course];
    }

    private function questionFor(Course $course, KnowledgeCard $card): Question
    {
        $question = Question::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $course->teacher_id,
            'title' => '測試題',
            'type' => Question::TYPE_CHOICE,
            'question_content' => '題幹',
        ]);
        $question->knowledgeCards()->attach($card->id);

        return $question;
    }

    /**
     * @return array<string, int>
     */
    private function tableCounts(): array
    {
        return [
            'chapters' => Chapter::query()->count(),
            'units' => Unit::query()->count(),
            'cards' => KnowledgeCard::query()->count(),
            'card_units' => DB::table('knowledge_card_unit')->count(),
            'card_questions' => DB::table('question_knowledge_cards')->count(),
            'card_contents' => crc32(KnowledgeCard::query()->orderBy('id')->pluck('content')->implode('|')),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function send(string $token, int $courseId, string $action, string $path, array $params = []): TestResponse
    {
        $url = "/api/v1/teacher/courses/{$courseId}/materials/import".($action === 'preview' ? '/preview' : '');

        return $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post($url, ['file' => $this->upload($path), ...$params]);
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
