<?php

namespace Tests\Feature;

use App\Models\Course;
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
        ]);

        $card->assertCreated()
            ->assertJsonPath('knowledge_card.title', 'PHP 變數介紹')
            ->assertJsonPath('knowledge_card.content', '變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數。');

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
        $otherCourse = Course::query()->where('name', '陳老師的課程')->firstOrFail();
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
