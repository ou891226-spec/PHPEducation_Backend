<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Teacher;
use App\Services\CourseService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_admin_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'account' => 'admin@school.edu.tw',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('user.name', '系統管理員');
    }

    public function test_teacher_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'account' => 'teacher@school.edu.tw',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'teacher')
            ->assertJsonPath('user.name', '許老師');
    }

    public function test_student_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'account' => 's1411131000',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'student')
            ->assertJsonPath('user.account', 's1411131000@nutc.edu.tw')
            ->assertJsonPath('user.student_no', '1411131000')
            ->assertJsonPath('user.name', '王小明');
    }

    public function test_wrong_password_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'account' => 's1411131000',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'statusCode' => 401,
                'message' => '帳號或密碼錯誤',
            ]);
    }

    public function test_logout(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJson(['message' => '登出成功']);
    }

    public function test_dashboard_for_teacher(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $response = $this->withToken($token)
            ->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('user.role', 'teacher')
            ->assertJsonStructure(['user', 'courses']);
    }

    public function test_teacher_course_crud(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $create = $this->withToken($token)->postJson('/api/v1/teacher/courses', [
            'name' => 'PHP 程式設計',
            'description' => '從基礎語法到實作練習',
            'semester' => '115-1',
        ]);

        $create->assertCreated()
            ->assertJsonPath('course.name', 'PHP 程式設計')
            ->assertJsonPath('course.description', '從基礎語法到實作練習');

        $courseId = $create->json('course.id');

        $this->withToken($token)
            ->getJson('/api/v1/teacher/courses')
            ->assertOk()
            ->assertJsonCount(1, 'courses');

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$courseId}")
            ->assertOk()
            ->assertJsonPath('course.id', $courseId);

        $this->withToken($token)
            ->putJson("/api/v1/teacher/courses/{$courseId}", [
                'name' => 'PHP 進階',
                'description' => '進階主題與專案實作',
                'semester' => '115-1',
            ])
            ->assertOk()
            ->assertJsonPath('course.name', 'PHP 進階')
            ->assertJsonPath('course.description', '進階主題與專案實作');

        $this->withToken($token)
            ->deleteJson("/api/v1/teacher/courses/{$courseId}")
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/teacher/courses')
            ->assertOk()
            ->assertJsonCount(0, 'courses');
    }

    public function test_teacher_cannot_modify_other_teachers_course(): void
    {
        $teacherA = Teacher::query()->where('account', 'teacher@school.edu.tw')->firstOrFail();
        $teacherB = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $otherCourse = Course::query()->where('teacher_id', $teacherB->id)->firstOrFail();

        $this->assertNotSame($teacherA->id, $otherCourse->teacher_id);

        $this->assertThrows(
            fn () => app(CourseService::class)->findForTeacher($teacherA, $otherCourse->id),
            ModelNotFoundException::class,
        );

        $teacherAToken = $this->loginToken('teacher@school.edu.tw');

        $this->withToken($teacherAToken)
            ->getJson("/api/v1/teacher/courses/{$otherCourse->id}")
            ->assertNotFound();

        $this->withToken($teacherAToken)
            ->putJson("/api/v1/teacher/courses/{$otherCourse->id}", [
                'name' => '非法修改',
                'semester' => '115-1',
            ])
            ->assertNotFound();

        $this->withToken($teacherAToken)
            ->deleteJson("/api/v1/teacher/courses/{$otherCourse->id}")
            ->assertNotFound();
    }

    public function test_student_cannot_access_teacher_course_crud(): void
    {
        $token = $this->loginToken('s1411131000');

        $this->withToken($token)
            ->getJson('/api/v1/teacher/courses')
            ->assertForbidden();

        $this->withToken($token)
            ->postJson('/api/v1/teacher/courses', [
                'name' => '非法課程',
                'semester' => '115-1',
            ])
            ->assertForbidden();
    }

    public function test_admin_cannot_access_teacher_course_crud(): void
    {
        $token = $this->loginToken('admin@school.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/teacher/courses')
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
