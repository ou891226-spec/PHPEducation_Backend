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
        ])->json('course.id');

        $topic = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/topics", [
            'name' => 'PHP 基礎',
        ]);

        $topic->assertCreated()
            ->assertJsonPath('topic.name', 'PHP 基礎')
            ->assertJsonPath('topic.item_count', 0);

        $topicId = $topic->json('topic.id');

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$courseId}/topics")
            ->assertOk()
            ->assertJsonCount(1, 'topics');

        $chapter = $this->withToken($token)->postJson("/api/v1/teacher/topics/{$topicId}/chapters", [
            'name' => '第一章 PHP 簡介',
        ]);

        $chapter->assertCreated()
            ->assertJsonPath('chapter.name', '第一章 PHP 簡介');

        $chapterId = $chapter->json('chapter.id');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/topics/{$topicId}", [
                'name' => 'PHP 入門',
            ])
            ->assertOk()
            ->assertJsonPath('topic.name', 'PHP 入門')
            ->assertJsonPath('topic.item_count', 1);

        $unit = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
            'name' => '變數',
        ]);

        $unit->assertCreated()
            ->assertJsonPath('unit.name', '變數');

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
            ->deleteJson("/api/v1/teacher/topics/{$topicId}")
            ->assertOk();

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$courseId}/topics")
            ->assertOk()
            ->assertJsonCount(0, 'topics');
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
            ->getJson("/api/v1/teacher/courses/{$otherCourse->id}/topics")
            ->assertNotFound();

        $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$otherCourse->id}/topics", [
                'name' => '非法主題',
            ])
            ->assertNotFound();
    }

    public function test_student_cannot_access_material_api(): void
    {
        $token = $this->loginToken('s1411131000');

        $this->withToken($token)
            ->getJson('/api/v1/teacher/courses/1/topics')
            ->assertForbidden();
    }

    public function test_deleting_topic_keeps_knowledge_cards_used_by_questions(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');
        [$courseId, $topicId, $keptCardId, $unusedCardId] = $this->seedTopicWithLinkedAndUnusedCards($token);

        $this->withToken($token)
            ->deleteJson("/api/v1/teacher/topics/{$topicId}")
            ->assertOk()
            ->assertJson(['message' => '主題已刪除']);

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$courseId}/topics")
            ->assertOk()
            ->assertJsonCount(0, 'topics');

        $this->assertDatabaseMissing('knowledge_cards', ['id' => $unusedCardId]);
        $this->assertDatabaseHas('knowledge_cards', [
            'id' => $keptCardId,
            'unit_id' => null,
        ]);
        $this->assertDatabaseHas('question_knowledge_cards', [
            'knowledge_card_id' => $keptCardId,
        ]);
    }

    public function test_deleting_knowledge_card_used_by_question_is_rejected(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');
        [, , $keptCardId] = $this->seedTopicWithLinkedAndUnusedCards($token);

        $this->withToken($token)
            ->deleteJson("/api/v1/teacher/knowledge-cards/{$keptCardId}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('knowledge_card');

        $this->assertDatabaseHas('knowledge_cards', ['id' => $keptCardId]);
    }

    public function test_teacher_cannot_create_topic_with_duplicate_name(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');
        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => 'PHP 程式設計',
            'description' => '從基礎語法到實作練習',
            'semester' => '115-1',
        ])->json('course.id');

        $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/topics", [
            'name' => 'PHP 基礎',
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/topics", [
            'name' => 'PHP 基礎',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int} courseId, topicId, linkedCardId, unusedCardId
     */
    private function seedTopicWithLinkedAndUnusedCards(string $token): array
    {
        $courseId = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => '知識卡刪除測試',
            'description' => '保留有題目關聯的知識卡',
            'semester' => '115-1',
        ])->json('course.id');

        $topicId = $this->withToken($token)->postJson("/api/v1/teacher/courses/{$courseId}/topics", [
            'name' => 'PHP 基礎',
        ])->json('topic.id');

        $chapterId = $this->withToken($token)->postJson("/api/v1/teacher/topics/{$topicId}/chapters", [
            'name' => '第一章',
        ])->json('chapter.id');

        $unitId = $this->withToken($token)->postJson("/api/v1/teacher/chapters/{$chapterId}/units", [
            'name' => '變數',
        ])->json('unit.id');

        $keptCardId = $this->withToken($token)->postJson("/api/v1/teacher/units/{$unitId}/knowledge-cards", [
            'title' => '有題目的卡',
            'content' => '會被保留',
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
        $question->knowledgeCards()->attach($keptCardId);

        return [$courseId, $topicId, $keptCardId, $unusedCardId];
    }

    private function loginToken(string $account): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'account' => $account,
            'password' => self::PASSWORD,
        ]);

        $response->assertOk();

        return $response->json('token');
    }
}
