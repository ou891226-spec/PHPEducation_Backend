<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class StatsApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_admin_can_get_counts(): void
    {
        $token = $this->loginToken('admin@school.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/stats')
            ->assertOk()
            ->assertJsonPath('teacher_count', 2)
            ->assertJsonPath('student_count', 1)
            ->assertJsonPath('course_count', 2)
            ->assertJsonPath('semester_course_count', 2)
            ->assertJsonPath('semester', '115-1');
    }

    public function test_teacher_cannot_get_counts(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/stats')
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
