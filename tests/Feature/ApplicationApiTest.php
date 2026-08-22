<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\StudentApplicationItems;
use App\Models\StudentApplications;
use App\Models\Teacher;
use App\Models\TeacherApplication;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class ApplicationApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_admin_can_list_teacher_applications(): void
    {
        TeacherApplication::query()->create([
            'name' => '林老師',
            'email' => 'lin@example.com',
            'reason' => '申請教師帳號',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('admin@school.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/teacher-applications?status=pending')
            ->assertOk()
            ->assertJsonPath('applications.0.name', '林老師')
            ->assertJsonPath('applications.0.email', 'lin@example.com')
            ->assertJsonPath('applications.0.status', 'pending');
    }

    public function test_teacher_cannot_list_teacher_applications(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/teacher-applications')
            ->assertForbidden();
    }

    public function test_admin_can_list_student_application_items(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'class_name' => '資應二甲',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131001',
            'name' => '李小華',
            'email' => 's1411131001@nutc.edu.tw',
        ]);

        $token = $this->loginToken('admin@school.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/student-applications?status=pending')
            ->assertOk()
            ->assertJsonPath('items.0.student_no', '1411131001')
            ->assertJsonPath('items.0.name', '李小華')
            ->assertJsonPath('items.0.status', 'pending')
            ->assertJsonPath('items.0.provider_teacher_name', '陳老師');
    }

    public function test_student_cannot_list_student_applications(): void
    {
        $token = $this->loginToken('s1411131000');

        $this->withToken($token)
            ->getJson('/api/v1/student-applications')
            ->assertForbidden();
    }

    public function test_teacher_can_list_pending_students_for_own_course(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計 (資應)')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'class_name' => '資應二甲',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131001',
            'name' => '李小華',
            'email' => 's1411131001@nutc.edu.tw',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$course->id}/student-applications")
            ->assertOk()
            ->assertJsonPath('items.0.student_no', '1411131001')
            ->assertJsonPath('items.0.status', 'pending')
            ->assertJsonPath('items.0.application_id', $application->id);
    }

    public function test_teacher_cannot_list_other_teachers_course_applications(): void
    {
        $course = Course::query()->where('name', '網際系統設計 (資應)')->firstOrFail();
        $token = $this->loginToken('teacher@school.edu.tw');

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$course->id}/student-applications")
            ->assertNotFound();
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
