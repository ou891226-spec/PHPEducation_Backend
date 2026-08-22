<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentApplicationItems;
use App\Models\StudentApplications;
use App\Models\Teacher;
use App\Models\TeacherApplication;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Mail;
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
        $course = Course::query()->where('name', '網際系統設計 (資應)')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應二甲',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131001',
            'name' => '李小華',
            'email' => 's1411131001@nutc.edu.tw',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('admin@school.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/student-applications?status=pending&course_id='.$course->id)
            ->assertOk()
            ->assertJsonPath('items.0.student_no', '1411131001')
            ->assertJsonPath('items.0.name', '李小華')
            ->assertJsonPath('items.0.status', 'pending')
            ->assertJsonPath('items.0.course_id', $course->id)
            ->assertJsonPath('items.0.provider_teacher_name', '陳老師')
            ->assertJsonPath('items.0.has_account', false);
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
            'course_id' => $course->id,
            'class_name' => '資應二甲',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131001',
            'name' => '李小華',
            'email' => 's1411131001@nutc.edu.tw',
            'status' => 'pending',
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

    public function test_admin_can_approve_selected_new_student(): void
    {
        Mail::fake();

        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計 (資應)')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應二甲',
            'status' => 'pending',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131001',
            'name' => '李小華',
            'email' => 's1411131001@nutc.edu.tw',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('admin@school.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'course_id' => $course->id,
                'item_ids' => [$item->id],
            ])
            ->assertOk()
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('enrolled_count', 1);

        $student = Student::query()->where('student_no', '1411131001')->first();
        $this->assertNotNull($student);
        $this->assertTrue(
            Enrollment::query()
                ->where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->exists(),
        );
        $this->assertSame('approved', $item->fresh()->status);
    }

    public function test_admin_can_enroll_existing_student_without_new_account(): void
    {
        Mail::fake();

        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計 (資應)')->firstOrFail();
        $existing = Student::query()->where('student_no', '1411131000')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應二甲',
            'status' => 'pending',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => $existing->student_no,
            'name' => $existing->name,
            'email' => $existing->email,
            'status' => 'pending',
        ]);

        $before = Student::query()->count();
        $token = $this->loginToken('admin@school.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'course_id' => $course->id,
                'item_ids' => [$item->id],
            ])
            ->assertOk()
            ->assertJsonPath('created_count', 0)
            ->assertJsonPath('enrolled_count', 1);

        $this->assertSame($before, Student::query()->count());
        $this->assertTrue(
            Enrollment::query()
                ->where('student_id', $existing->id)
                ->where('course_id', $course->id)
                ->exists(),
        );
    }

    public function test_admin_can_approve_one_student_and_leave_the_other_pending(): void
    {
        Mail::fake();

        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計 (資應)')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應二甲',
            'status' => 'pending',
        ]);

        $first = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411132001',
            'name' => '林小安',
            'email' => 's1411132001@nutc.edu.tw',
            'status' => 'pending',
        ]);

        $second = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411132002',
            'name' => '張小華',
            'email' => 's1411132002@nutc.edu.tw',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('admin@school.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'course_id' => $course->id,
                'item_ids' => [$first->id],
            ])
            ->assertOk();

        $this->assertSame('approved', $first->fresh()->status);
        $this->assertSame('pending', $second->fresh()->status);
        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_teacher_cannot_approve_selected_students(): void
    {
        $course = Course::query()->where('name', '網際系統設計 (資應)')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'course_id' => $course->id,
                'item_ids' => [1],
            ])
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
