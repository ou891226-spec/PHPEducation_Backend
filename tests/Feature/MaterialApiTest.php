<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class MaterialApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_teacher_can_drill_down_and_manage_materials(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => 'PHP 程式設計',
            'description' => '從基礎語法到實作練習',
            'semester' => '115-1',
            'class_name' => '資應二甲',
        ])->json('course.id');

        $chapter = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/chapters", [
            'name' => '第一章 PHP 簡介',
        ]);

        $chapter->assertCreated()
            ->assertJsonPath('chapter.name', '第一章 PHP 簡介')
            ->assertJsonPath('chapter.item_count', 0);

        $chapterId = $chapter->json('chapter.id');

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$courseId}/chapters")
            ->assertOk()
            ->assertJsonCount(1, 'chapters');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/chapters/{$chapterId}", [
                'name' => '第一章 PHP 入門',
            ])
            ->assertOk()
            ->assertJsonPath('chapter.name', '第一章 PHP 入門');

        $unit = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
            'name' => '變數',
        ]);

        $unit->assertCreated()
            ->assertJsonPath('unit.name', '變數')
            ->assertJsonPath('unit.status', 'draft');

        $unitId = $unit->json('unit.id');

        $card = $this->withToken($token)->postJson("/api/v1/teacher/units/{$unitId}/knowledge-cards", [
            'title' => 'PHP 變數介紹',
            'content' => '變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數。',
            'example' => '$name = "PHP";',
        ]);

        $card->assertCreated()
            ->assertJsonPath('knowledge_card.title', 'PHP 變數介紹')
            ->assertJsonPath('knowledge_card.content', '變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數。')
            ->assertJsonPath('knowledge_card.example', '$name = "PHP";');

        $cardId = $card->json('knowledge_card.id');

        $this->withToken($token)
            ->getJson("/api/v1/teacher/units/{$unitId}/knowledge-cards")
            ->assertOk()
            ->assertJsonCount(1, 'knowledge_cards');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/knowledge-cards/{$cardId}", [
                'title' => '變數命名規則',
                'content' => '變數名稱需以字母或底線開頭。',
            ])
            ->assertOk()
            ->assertJsonPath('knowledge_card.title', '變數命名規則');

        $this->withToken($token)
            ->deleteJson("/api/v1/teacher/knowledge-cards/{$cardId}")
            ->assertOk()
            ->assertJson(['message' => '知識卡已刪除']);

        $this->withToken($token)
            ->getJson("/api/v1/teacher/units/{$unitId}/knowledge-cards")
            ->assertOk()
            ->assertJsonCount(0, 'knowledge_cards');

        $this->withToken($token)
            ->deleteJson("/api/v1/teacher/units/{$unitId}")
            ->assertOk();

        $this->withToken($token)
            ->deleteJson("/api/v1/teacher/chapters/{$chapterId}")
            ->assertOk();

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$courseId}/chapters")
            ->assertOk()
            ->assertJsonCount(0, 'chapters');
    }

    public function test_sort_order_must_be_unique_within_parent_scope(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '排序測試課',
            'description' => 'sort_order',
            'semester' => '115-1',
            'class_name' => '資應二甲',
        ])->json('course.id');

        $chapter1 = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/chapters", [
            'name' => '第一章',
            'sort_order' => 1,
        ])->assertCreated()->json('chapter');

        $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/chapters", [
            'name' => '重複章',
            'sort_order' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['sort_order']);

        $chapter2Id = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/chapters", [
            'name' => '第二章',
            'sort_order' => 2,
        ])->assertCreated()->json('chapter.id');

        $unitA = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapter1['id']}/units", [
            'name' => '單元 A',
            'sort_order' => 1,
        ])->assertCreated()->json('unit');

        $unitBId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapter1['id']}/units", [
            'name' => '單元 B',
            'sort_order' => 2,
        ])->assertCreated()->json('unit.id');

        $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapter1['id']}/units", [
            'name' => '單元 C 搶位',
            'sort_order' => 2,
        ])->assertStatus(422)->assertJsonPath('errors.sort_order.0', 'sort_order 已存在');

        // 不同章節可以同號
        $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapter2Id}/units", [
            'name' => '另一章的單元',
            'sort_order' => 1,
        ])->assertCreated();

        // 更新成自己原本的 sort_order 可以
        $this->withToken($token)->putJson("/api/v1/teacher/units/{$unitBId}", [
            'name' => '單元 B',
            'sort_order' => 2,
        ])->assertOk()->assertJsonPath('unit.sort_order', 2);

        // 搶別人的號不行，原資料不變
        $this->withToken($token)->putJson("/api/v1/teacher/units/{$unitBId}", [
            'name' => '單元 B',
            'sort_order' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['sort_order']);

        $this->withToken($token)
            ->getJson("/api/v1/teacher/chapters/{$chapter1['id']}/units")
            ->assertOk()
            ->assertJsonFragment(['id' => $unitBId, 'name' => '單元 B', 'sort_order' => 2])
            ->assertJsonFragment(['id' => $unitA['id'], 'sort_order' => 1]);

        $cardAId = $this->withToken($token)->postJson("/api/v1/teacher/units/{$unitA['id']}/knowledge-cards", [
            'title' => '卡 A',
            'content' => '內容 A',
            'sort_order' => 0,
        ])->assertCreated()->json('knowledge_card.id');

        $this->withToken($token)->postJson("/api/v1/teacher/units/{$unitA['id']}/knowledge-cards", [
            'title' => '卡 B 搶位',
            'content' => '內容 B',
            'sort_order' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors(['sort_order']);

        $this->withToken($token)->putJson("/api/v1/teacher/knowledge-cards/{$cardAId}", [
            'title' => '卡 A',
            'content' => '內容 A',
            'sort_order' => 0,
        ])->assertOk()->assertJsonPath('knowledge_card.sort_order', 0);
    }

    public function test_teacher_cannot_access_other_teachers_materials(): void
    {
        $otherTeacher = \App\Models\Teacher::query()
            ->where('account', 'teacher2@school.edu.tw')
            ->firstOrFail();

        $otherCourse = Course::query()
            ->where('teacher_id', $otherTeacher->id)
            ->orderBy('id')
            ->firstOrFail();
        $token = $this->loginToken('teacher@school.edu.tw');

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$otherCourse->id}/chapters")
            ->assertNotFound();

        $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$otherCourse->id}/chapters", [
                'name' => '非法章節',
            ])
            ->assertNotFound();
    }

    public function test_student_cannot_access_material_api(): void
    {
        $token = $this->loginToken('s1411131000');

        $this->withToken($token)
            ->getJson('/api/v1/teacher/courses/1/chapters')
            ->assertForbidden();
    }

    public function test_deleting_chapter_hard_deletes_knowledge_cards_even_if_used_by_questions(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');
        [$courseId, $chapterId, $linkedCardId, $unusedCardId, $questionId] = $this->seedChapterWithLinkedAndUnusedCards($token);

        $this->withToken($token)
            ->deleteJson("/api/v1/teacher/chapters/{$chapterId}")
            ->assertOk()
            ->assertJson(['message' => '章節已刪除']);

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$courseId}/chapters")
            ->assertOk()
            ->assertJsonCount(0, 'chapters');

        $this->assertDatabaseMissing('knowledge_cards', ['id' => $unusedCardId]);
        $this->assertDatabaseMissing('knowledge_cards', ['id' => $linkedCardId]);
        $this->assertDatabaseMissing('question_knowledge_cards', [
            'knowledge_card_id' => $linkedCardId,
        ]);
        $this->assertDatabaseHas('questions', ['id' => $questionId]);
    }

    public function test_deleting_knowledge_card_used_by_question_hard_deletes_and_detaches(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');
        [, , $linkedCardId, , $questionId] = $this->seedChapterWithLinkedAndUnusedCards($token);

        $this->withToken($token)
            ->deleteJson("/api/v1/teacher/knowledge-cards/{$linkedCardId}")
            ->assertOk()
            ->assertJson(['message' => '知識卡已刪除']);

        $this->assertDatabaseMissing('knowledge_cards', ['id' => $linkedCardId]);
        $this->assertDatabaseMissing('question_knowledge_cards', [
            'knowledge_card_id' => $linkedCardId,
        ]);
        $this->assertDatabaseHas('questions', ['id' => $questionId]);
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int, 4: int} courseId, chapterId, linkedCardId, unusedCardId, questionId
     */
    private function seedChapterWithLinkedAndUnusedCards(string $token): array
    {
        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '知識卡刪除測試',
            'description' => '刪教材時硬刪知識卡',
            'semester' => '115-1',
            'class_name' => '資應二甲',
        ])->json('course.id');

        $chapterId = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/chapters", [
            'name' => '第一章',
        ])->json('chapter.id');

        $unitId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
            'name' => '變數',
        ])->json('unit.id');

        $linkedCardId = $this->withToken($token)->postJson("/api/v1/teacher/units/{$unitId}/knowledge-cards", [
            'title' => '有題目的卡',
            'content' => '會被硬刪',
        ])->json('knowledge_card.id');

        $unusedCardId = $this->withToken($token)->postJson("/api/v1/teacher/units/{$unitId}/knowledge-cards", [
            'title' => '沒題目的卡',
            'content' => '會被刪掉',
        ])->json('knowledge_card.id');

        $course = Course::query()->findOrFail($courseId);
        $question = Question::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $course->teacher_id,
            'title' => '變數題',
            'type' => Question::TYPE_CHOICE,
            'question_content' => '題幹',
        ]);
        $question->knowledgeCards()->attach($linkedCardId);

        return [$courseId, $chapterId, $linkedCardId, $unusedCardId, $question->id];
    }

    public function test_teacher_can_toggle_unit_status_and_student_only_sees_published(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '草稿單元測試課',
            'semester' => '115-1',
            'class_name' => '資應',
            'description' => '單元草稿測試',
        ])->assertCreated()->json('course.id');

        $chapterId = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/chapters", [
            'name' => '第一章',
        ])->assertCreated()->json('chapter.id');

        $draftUnitId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
            'name' => '草稿單元',
        ])->assertCreated()->assertJsonPath('unit.status', 'draft')->json('unit.id');

        $publishedUnitId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
            'name' => '開放單元',
            'status' => 'published',
        ])->assertCreated()->assertJsonPath('unit.status', 'published')->json('unit.id');

        $this->withToken($token)
            ->postJson("/api/v1/teacher/units/{$draftUnitId}/knowledge-cards", [
                'title' => '草稿卡',
                'content' => '學生不該看到',
            ])
            ->assertCreated();

        $this->withToken($token)
            ->postJson("/api/v1/teacher/units/{$publishedUnitId}/knowledge-cards", [
                'title' => '開放卡',
                'content' => '學生看得到',
            ])
            ->assertCreated();

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$courseId}/tree")
            ->assertOk()
            ->assertJsonPath('course.chapters.0.units.0.status', 'draft')
            ->assertJsonPath('course.chapters.0.units.1.status', 'published');

        $student = \App\Models\Student::query()->where('student_no', '1411131000')->firstOrFail();
        \App\Models\Enrollment::query()->firstOrCreate([
            'student_id' => $student->id,
            'course_id' => $courseId,
        ]);

        $studentToken = $this->loginToken('s1411131000');

        $this->withToken($studentToken)
            ->getJson("/api/v1/student/courses/{$courseId}/graph")
            ->assertOk()
            ->assertJsonCount(1, 'graph.chapters.0.units')
            ->assertJsonPath('graph.chapters.0.units.0.name', '開放單元')
            ->assertJsonPath('graph.chapters.0.units.0.status', 'published');

        $this->withToken($studentToken)
            ->getJson("/api/v1/student/chapters/{$chapterId}/units")
            ->assertOk()
            ->assertJsonCount(1, 'units')
            ->assertJsonPath('units.0.name', '開放單元');

        $this->withToken($studentToken)
            ->getJson("/api/v1/student/units/{$draftUnitId}/knowledge-cards")
            ->assertNotFound();

        $token = $this->loginToken('teacher@school.edu.tw');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/units/{$draftUnitId}", [
                'name' => '草稿單元',
                'status' => 'published',
            ])
            ->assertOk()
            ->assertJsonPath('unit.status', 'published');

        $studentToken = $this->loginToken('s1411131000');

        $this->withToken($studentToken)
            ->getJson("/api/v1/student/courses/{$courseId}/graph")
            ->assertOk()
            ->assertJsonCount(2, 'graph.chapters.0.units');

        $token = $this->loginToken('teacher@school.edu.tw');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/units/{$publishedUnitId}", [
                'name' => '開放單元',
                'status' => 'draft',
            ])
            ->assertOk()
            ->assertJsonPath('unit.status', 'draft');

        $studentToken = $this->loginToken('s1411131000');

        $this->withToken($studentToken)
            ->getJson("/api/v1/student/units/{$publishedUnitId}/knowledge-cards")
            ->assertNotFound();
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
