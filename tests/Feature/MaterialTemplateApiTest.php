<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class MaterialTemplateApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_any_teacher_can_download_import_template(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $response = $this->withToken($token)
            ->get('/api/v1/teacher/materials/template');

        $response->assertOk()
            ->assertDownload('material_import_template.xlsx')
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->assertStringContainsString(
            "filename*=UTF-8''".rawurlencode('教材匯入範本.xlsx'),
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_another_teacher_can_also_download_import_template(): void
    {
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->get('/api/v1/teacher/materials/template')
            ->assertOk()
            ->assertDownload('material_import_template.xlsx');
    }

    public function test_student_cannot_download_material_template(): void
    {
        $token = $this->loginToken('s1411131000');

        $this->withToken($token)
            ->get('/api/v1/teacher/materials/template')
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
