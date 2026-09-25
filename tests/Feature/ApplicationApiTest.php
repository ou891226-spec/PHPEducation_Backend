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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\Support\SimpleXlsx;
use Tests\TestCase;

class ApplicationApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_admin_can_list_teacher_applications(): void
    {
        TeacherApplication::query()->create([
            'name' => '林老師',
            'email' => 'lin@example.com',
            'account' => 'teacher_lin',
            'reason' => '申請教師帳號',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('admin@nutc.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/teacher-applications?status=pending')
            ->assertOk()
            ->assertJsonPath('applications.0.name', '林老師')
            ->assertJsonPath('applications.0.email', 'lin@example.com')
            ->assertJsonPath('applications.0.account', 'teacher_lin')
            ->assertJsonPath('applications.0.status', 'pending');
    }

    public function test_teacher_application_creation_and_approval_flow(): void
    {
        Mail::fake();

        // 1. 提交教師申請
        $storeResponse = $this->postJson('/api/v1/teacher-applications', [
            'name' => '張老師',
            'email' => 'chang@example.com',
            'account' => 'teacher_chang',
            'reason' => '想使用平台教學',
        ]);

        $storeResponse->assertCreated()
            ->assertJsonPath('data.name', '張老師')
            ->assertJsonPath('data.account', 'teacher_chang');

        $appId = $storeResponse->json('data.id');

        // 2. 審核教師申請（需 Admin 權限）
        $adminToken = $this->loginToken('admin@nutc.edu.tw');

        $approveResponse = $this->withToken($adminToken)
            ->postJson("/api/v1/teacher-applications/{$appId}/approve");
        $approveResponse->assertOk()
            ->assertJsonPath('data.account', 'teacher_chang')
            ->assertJsonPath('data.email', 'chang@example.com');

        $teacher = Teacher::query()->where('account', 'teacher_chang')->first();
        $this->assertNotNull($teacher);
        $this->assertSame('chang@example.com', $teacher->email);

        Mail::assertSent(\App\Mail\TeacherAccountCreated::class, function ($mail) use ($teacher) {
            return $mail->account === 'teacher_chang'
                && !empty($mail->password);
        });
    }

    public function test_non_admin_cannot_approve_teacher_application(): void
    {
        $app = TeacherApplication::query()->create([
            'name' => '測試老師',
            'email' => 'test_teacher_non_admin@example.com',
            'account' => 'teacher_non_admin',
            'reason' => '申請',
            'status' => 'pending',
        ]);

        // 未登入 (401)
        $this->postJson("/api/v1/teacher-applications/{$app->id}/approve")
            ->assertUnauthorized();

        // 教師嘗試審核 (403)
        $teacherToken = $this->loginToken('teacher@school.edu.tw');
        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher-applications/{$app->id}/approve")
            ->assertForbidden();

        // 學生嘗試審核 (403)
        $studentToken = $this->loginToken('s1411131000');
        $this->withToken($studentToken)
            ->postJson("/api/v1/teacher-applications/{$app->id}/approve")
            ->assertForbidden();
    }

    public function test_teacher_cannot_submit_duplicate_teacher_application(): void
    {
        TeacherApplication::query()->create([
            'name' => '林老師',
            'email' => 'lin_dup@example.com',
            'account' => 'teacher_lin_dup',
            'reason' => '申請教師帳號',
            'status' => 'pending',
        ]);

        $this->postJson('/api/v1/teacher-applications', [
            'name' => '林老師2',
            'email' => 'lin_dup@example.com',
            'account' => 'teacher_lin_dup2',
            'reason' => '重複信箱',
        ])->assertStatus(422);

        $this->postJson('/api/v1/teacher-applications', [
            'name' => '林老師3',
            'email' => 'lin_dup3@example.com',
            'account' => 'teacher_lin_dup',
            'reason' => '重複帳號',
        ])->assertStatus(422);
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
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131001',
            'name' => '李小華',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('admin@nutc.edu.tw');

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
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131001',
            'name' => '李小華',
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

    public function test_teacher_list_includes_approved_students(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131001',
            'name' => '李小華',
            'status' => 'approved',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131002',
            'name' => '陳小名',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$course->id}/student-applications")
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.status', 'approved')
            ->assertJsonPath('items.1.status', 'pending');
    }

    public function test_teacher_list_is_sorted_by_student_no(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131021',
            'name' => '先匯入',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131015',
            'name' => '後新增',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$course->id}/student-applications")
            ->assertOk()
            ->assertJsonPath('items.0.student_no', '1411131015')
            ->assertJsonPath('items.1.student_no', '1411131021');
    }

    public function test_teacher_cannot_list_other_teachers_course_applications(): void
    {
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $token = $this->loginToken('teacher@school.edu.tw');

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$course->id}/student-applications")
            ->assertNotFound();
    }

    public function test_teacher_can_add_one_student_with_student_no_only(): void
    {
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$course->id}/student-applications", [
                'student_no' => '1411139999',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('student_application_items', [
            'student_no' => '1411139999',
            'name' => '',
            'status' => 'pending',
        ]);
    }

    public function test_teacher_can_add_student_with_custom_email_and_approve_uses_it(): void
    {
        Mail::fake();

        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$course->id}/student-applications", [
                'students' => [[
                    'student_no' => '1411138888',
                    'name' => '自訂信箱生',
                    'email' => 'custom.student@example.com',
                ]],
            ])
            ->assertCreated();

        $item = StudentApplicationItems::query()
            ->where('student_no', '1411138888')
            ->firstOrFail();

        $this->assertSame('custom.student@example.com', $item->email);
        $this->assertSame('pending', $item->status);

        $adminToken = $this->loginToken('admin@nutc.edu.tw');

        $this->withToken($adminToken)
            ->postJson('/api/v1/student-applications/approve', [
                'course_ids' => [$course->id],
                'item_ids' => [$item->id],
            ])
            ->assertOk()
            ->assertJsonPath('created_count', 1);

        $this->assertDatabaseHas('students', [
            'student_no' => '1411138888',
            'name' => '自訂信箱生',
            'email' => 'custom.student@example.com',
        ]);
    }

    public function test_teacher_adding_existing_student_enrolls_immediately(): void
    {
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資管')->firstOrFail();
        $existing = Student::query()->where('student_no', '1411131000')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$course->id}/student-applications", [
                'student_no' => $existing->student_no,
                'name' => $existing->name,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('student_application_items', [
            'student_no' => $existing->student_no,
            'name' => $existing->name,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $existing->id,
            'course_id' => $course->id,
        ]);

        $this->withToken($token)
            ->getJson("/api/v1/teacher/courses/{$course->id}/student-applications?status=approved")
            ->assertOk()
            ->assertJsonPath('items.0.student_no', $existing->student_no)
            ->assertJsonPath('items.0.name', $existing->name)
            ->assertJsonPath('items.0.has_account', true);
    }

    public function test_teacher_adding_existing_student_uses_account_name_when_typed_wrong(): void
    {
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資管')->firstOrFail();
        $existing = Student::query()->where('student_no', '1411131000')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$course->id}/student-applications", [
                'student_no' => $existing->student_no,
                'name' => '王小名',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('student_application_items', [
            'student_no' => $existing->student_no,
            'name' => $existing->name,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $existing->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_teacher_can_lookup_existing_student_by_student_no(): void
    {
        $existing = Student::query()->where('student_no', '1411131000')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/teacher/students/lookup?student_no=s'.$existing->student_no)
            ->assertOk()
            ->assertJsonPath('has_account', true)
            ->assertJsonPath('student_no', $existing->student_no)
            ->assertJsonPath('name', $existing->name)
            ->assertJsonPath('email', $existing->email)
            ->assertJsonPath('matches.0.student_no', $existing->student_no)
            ->assertJsonPath('matches.0.email', $existing->email);

        $this->withToken($token)
            ->getJson('/api/v1/teacher/students/lookup?student_no=9999999999')
            ->assertOk()
            ->assertJsonPath('has_account', false)
            ->assertJsonPath('student_no', '9999999999')
            ->assertJsonPath('name', null)
            ->assertJsonPath('email', 's9999999999@nutc.edu.tw')
            ->assertJsonCount(0, 'matches');
    }

    public function test_teacher_can_lookup_existing_student_by_name(): void
    {
        $existing = Student::query()->where('student_no', '1411131000')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/teacher/students/lookup?name='.urlencode($existing->name))
            ->assertOk()
            ->assertJsonPath('has_account', true)
            ->assertJsonPath('student_no', $existing->student_no)
            ->assertJsonPath('name', $existing->name)
            ->assertJsonPath('email', $existing->email)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.email', $existing->email);
    }

    public function test_teacher_lookup_returns_custom_email_for_existing_student(): void
    {
        $student = Student::query()->create([
            'student_no' => '1411136600',
            'name' => '自訂信箱生',
            'class_name' => '資應',
            'email' => 'custom.lookup@example.com',
            'password' => 'password',
        ]);
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/teacher/students/lookup?student_no='.$student->student_no)
            ->assertOk()
            ->assertJsonPath('has_account', true)
            ->assertJsonPath('email', 'custom.lookup@example.com')
            ->assertJsonPath('matches.0.email', 'custom.lookup@example.com');
    }

    public function test_teacher_adding_mixed_students_enrolls_existing_and_keeps_new_pending(): void
    {
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資管')->firstOrFail();
        $existing = Student::query()->where('student_no', '1411131000')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$course->id}/student-applications", [
                'students' => [
                    ['student_no' => $existing->student_no, 'name' => $existing->name],
                    ['student_no' => '1411139001', 'name' => '新同學'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('student_application_items', [
            'student_no' => $existing->student_no,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $existing->id,
            'course_id' => $course->id,
        ]);
        $this->assertDatabaseHas('student_application_items', [
            'student_no' => '1411139001',
            'name' => '新同學',
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('students', [
            'student_no' => '1411139001',
        ]);
    }

    public function test_teacher_can_add_one_student_for_own_course(): void
    {
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$course->id}/student-applications", [
                'student_no' => 's1411138888',
                'name' => '補漏學生',
            ])
            ->assertCreated()
            ->assertJsonPath('data.class_name', '資應')
            ->assertJsonPath('data.course_id', $course->id);

        $this->assertDatabaseHas('student_application_items', [
            'student_no' => '1411138888',
            'name' => '補漏學生',
            'status' => 'pending',
        ]);
    }

    public function test_teacher_can_add_multiple_students_for_own_course(): void
    {
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$course->id}/student-applications", [
                'students' => [
                    ['student_no' => '1411138801', 'name' => '甲生'],
                    ['student_no' => 's1411138802', 'name' => '乙生'],
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('student_application_items', [
            'student_no' => '1411138801',
            'name' => '甲生',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('student_application_items', [
            'student_no' => '1411138802',
            'name' => '乙生',
            'status' => 'pending',
        ]);
    }

    public function test_teacher_cannot_add_duplicate_student_for_course(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411138888',
            'name' => '已在名冊',
            'status' => 'approved',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$course->id}/student-applications", [
                'student_no' => '1411138888',
                'name' => '再加一次',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.student_no.0', '該課已有相同學號');
    }

    public function test_teacher_cannot_add_student_to_other_teachers_course(): void
    {
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $token = $this->loginToken('teacher@school.edu.tw');

        $this->withToken($token)
            ->postJson("/api/v1/teacher/courses/{$course->id}/student-applications", [
                'student_no' => '1411137777',
                'name' => '不該成功',
            ])
            ->assertNotFound();
    }

    public function test_teacher_can_remove_pending_student_from_own_course(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411137701',
            'name' => '待移除',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->deleteJson("/api/v1/teacher/courses/{$course->id}/student-applications/{$item->id}")
            ->assertOk()
            ->assertJsonPath('message', '已從課程移除');

        $this->assertDatabaseMissing('student_application_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('student_applications', ['id' => $application->id]);
    }

    public function test_teacher_can_update_pending_student_name_and_student_no(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411136601',
            'name' => '打錯名',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/courses/{$course->id}/student-applications/{$item->id}", [
                'student_no' => 's1411136602',
                'name' => '改正姓名',
            ])
            ->assertOk()
            ->assertJsonPath('message', '學生資料已更新')
            ->assertJsonPath('item.student_no', '1411136602')
            ->assertJsonPath('item.name', '改正姓名')
            ->assertJsonPath('item.email', 's1411136602@nutc.edu.tw');

        $this->assertDatabaseHas('student_application_items', [
            'id' => $item->id,
            'student_no' => '1411136602',
            'name' => '改正姓名',
            'status' => 'pending',
        ]);
    }

    public function test_teacher_can_update_pending_student_email(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411136603',
            'name' => '待開通',
            'email' => 's1411136603@nutc.edu.tw',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/courses/{$course->id}/student-applications/{$item->id}", [
                'student_no' => '1411136603',
                'name' => '待開通',
                'email' => 'pending.custom@nutc.edu.tw',
            ])
            ->assertOk()
            ->assertJsonPath('item.email', 'pending.custom@nutc.edu.tw')
            ->assertJsonPath('item.has_account', false);

        $this->assertDatabaseHas('student_application_items', [
            'id' => $item->id,
            'student_no' => '1411136603',
            'email' => 'pending.custom@nutc.edu.tw',
            'status' => 'pending',
        ]);
    }

    public function test_teacher_can_update_approved_student_student_no_without_changing_account_name(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $student = Student::query()->where('student_no', '1411131000')->firstOrFail();
        $originalName = $student->name;

        Enrollment::query()->firstOrCreate([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'approved',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => $student->student_no,
            'name' => $student->name,
            'status' => 'approved',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/courses/{$course->id}/student-applications/{$item->id}", [
                'student_no' => '1411131999',
                'name' => '測試帳號改名',
            ])
            ->assertOk()
            ->assertJsonPath('item.student_no', '1411131999')
            ->assertJsonPath('item.name', $originalName)
            ->assertJsonPath('item.has_account', true);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'student_no' => '1411131999',
            'name' => $originalName,
            'email' => 's1411131999@nutc.edu.tw',
        ]);
    }

    public function test_teacher_can_update_approved_student_email(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $student = Student::query()->where('student_no', '1411131000')->firstOrFail();

        Enrollment::query()->firstOrCreate([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'approved',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => $student->student_no,
            'name' => $student->name,
            'status' => 'approved',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/courses/{$course->id}/student-applications/{$item->id}", [
                'student_no' => $student->student_no,
                'name' => $student->name,
                'email' => 'custom.student@nutc.edu.tw',
            ])
            ->assertOk()
            ->assertJsonPath('item.email', 'custom.student@nutc.edu.tw')
            ->assertJsonPath('item.has_account', true);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'student_no' => $student->student_no,
            'email' => 'custom.student@nutc.edu.tw',
        ]);
    }

    public function test_teacher_can_update_student_no_with_custom_email_without_overwriting(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $student = Student::query()->where('student_no', '1411131000')->firstOrFail();
        $originalName = $student->name;

        Enrollment::query()->firstOrCreate([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'approved',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => $student->student_no,
            'name' => $student->name,
            'status' => 'approved',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/courses/{$course->id}/student-applications/{$item->id}", [
                'student_no' => '1411131888',
                'name' => '測試帳號改名',
                'email' => 'keep.custom@nutc.edu.tw',
            ])
            ->assertOk()
            ->assertJsonPath('item.student_no', '1411131888')
            ->assertJsonPath('item.name', $originalName)
            ->assertJsonPath('item.email', 'keep.custom@nutc.edu.tw');

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'student_no' => '1411131888',
            'name' => $originalName,
            'email' => 'keep.custom@nutc.edu.tw',
        ]);
    }

    public function test_teacher_cannot_update_student_email_to_duplicate(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $student = Student::query()->where('student_no', '1411131000')->firstOrFail();

        $other = Student::query()->create([
            'student_no' => '1411137777',
            'name' => '其他學生',
            'class_name' => '資應',
            'email' => 'taken.email@nutc.edu.tw',
            'password' => 'password',
        ]);

        Enrollment::query()->firstOrCreate([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'approved',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => $student->student_no,
            'name' => $student->name,
            'status' => 'approved',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/courses/{$course->id}/student-applications/{$item->id}", [
                'student_no' => $student->student_no,
                'name' => $student->name,
                'email' => $other->email,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_teacher_cannot_update_student_to_duplicate_student_no_on_course(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411136701',
            'name' => '甲',
            'status' => 'pending',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411136702',
            'name' => '乙',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->putJson("/api/v1/teacher/courses/{$course->id}/student-applications/{$item->id}", [
                'student_no' => '1411136701',
                'name' => '乙改撞號',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.student_no.0', '該課已有相同學號');
    }

    public function test_teacher_can_remove_approved_student_without_deleting_account(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $student = Student::query()->where('student_no', '1411131000')->firstOrFail();

        Enrollment::query()->firstOrCreate([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'approved',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => $student->student_no,
            'name' => $student->name,
            'status' => 'approved',
        ]);

        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->deleteJson("/api/v1/teacher/courses/{$course->id}/student-applications/{$item->id}")
            ->assertOk();

        $this->assertDatabaseMissing('student_application_items', ['id' => $item->id]);
        $this->assertFalse(
            Enrollment::query()
                ->where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->exists(),
        );
        $this->assertNotNull(Student::query()->whereKey($student->id)->first());
    }

    public function test_teacher_cannot_remove_student_from_other_teachers_course(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411137702',
            'name' => '不該被刪',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('teacher@school.edu.tw');

        $this->withToken($token)
            ->deleteJson("/api/v1/teacher/courses/{$course->id}/student-applications/{$item->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('student_application_items', ['id' => $item->id]);
    }

    public function test_admin_can_approve_all_pending_by_source_course(): void
    {
        Mail::fake();

        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $courseA = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $courseB = Course::query()->where('name', '網際系統設計')->where('class_name', '資管')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $courseA->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        $first = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411134001',
            'name' => '',
            'status' => 'pending',
        ]);

        $second = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411134002',
            'name' => '',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('admin@nutc.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'source_course_id' => $courseA->id,
                'course_ids' => [$courseA->id, $courseB->id],
            ])
            ->assertOk()
            ->assertJsonPath('activated_count', 2)
            ->assertJsonPath('created_count', 2)
            ->assertJsonPath('enrolled_count', 4);

        $this->assertSame('approved', $first->fresh()->status);
        $this->assertSame('approved', $second->fresh()->status);
        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_admin_can_approve_selected_new_student(): void
    {
        Mail::fake();

        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411131001',
            'name' => '李小華',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('admin@nutc.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'course_ids' => [$course->id],
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
        $courseApplied = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $courseTarget = Course::query()->where('name', '網際系統設計')->where('class_name', '資管')->firstOrFail();
        $existing = Student::query()->where('student_no', '1411131000')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $courseApplied->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => $existing->student_no,
            'name' => $existing->name,
            'status' => 'pending',
        ]);

        $before = Student::query()->count();
        $token = $this->loginToken('admin@nutc.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'course_ids' => [$courseTarget->id],
                'item_ids' => [$item->id],
            ])
            ->assertOk()
            ->assertJsonPath('created_count', 0)
            ->assertJsonPath('enrolled_count', 1);

        $this->assertSame($before, Student::query()->count());
        $this->assertTrue(
            Enrollment::query()
                ->where('student_id', $existing->id)
                ->where('course_id', $courseTarget->id)
                ->exists(),
        );
        $this->assertSame('approved', $item->fresh()->status);
    }

    public function test_admin_can_approve_one_student_and_leave_the_other_pending(): void
    {
        Mail::fake();

        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        $first = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411132001',
            'name' => '林小安',
            'status' => 'pending',
        ]);

        $second = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411132002',
            'name' => '張小華',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('admin@nutc.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'course_ids' => [$course->id],
                'item_ids' => [$first->id],
            ])
            ->assertOk();

        $this->assertSame('approved', $first->fresh()->status);
        $this->assertSame('pending', $second->fresh()->status);
        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_teacher_cannot_approve_selected_students(): void
    {
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'course_ids' => [$course->id],
                'item_ids' => [1],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_approve_student_into_multiple_courses(): void
    {
        Mail::fake();

        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $courseA = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $courseB = Course::query()->where('name', '網際系統設計')->where('class_name', '資管')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $courseA->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411133001',
            'name' => '多課開通',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('admin@nutc.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'course_ids' => [$courseA->id, $courseB->id],
                'item_ids' => [$item->id],
            ])
            ->assertOk()
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('enrolled_count', 2)
            ->assertJsonPath('message', '已開通課程。');

        $student = Student::query()->where('student_no', '1411133001')->firstOrFail();
        $this->assertTrue(
            Enrollment::query()->where('student_id', $student->id)->where('course_id', $courseA->id)->exists(),
        );
        $this->assertTrue(
            Enrollment::query()->where('student_id', $student->id)->where('course_id', $courseB->id)->exists(),
        );
        $this->assertSame('approved', $item->fresh()->status);
    }

    public function test_admin_approve_accepts_legacy_course_id(): void
    {
        Mail::fake();

        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        $item = StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411133002',
            'name' => '舊欄位相容',
            'status' => 'pending',
        ]);

        $token = $this->loginToken('admin@nutc.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'course_id' => $course->id,
                'item_ids' => [$item->id],
            ])
            ->assertOk()
            ->assertJsonPath('enrolled_count', 1);
    }

    public function test_teacher_can_submit_student_application_without_email_and_approve_it(): void
    {
        Mail::fake();

        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $path = $this->rosterXlsxPath([
            ['學號', '姓名'],
            ['1411139001', '王小明'],
        ]);

        $storeResponse = $this->post('/api/v1/teacher/student-applications', [
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'file' => $this->upload($path),
        ], ['Accept' => 'application/json']);

        $storeResponse->assertCreated()
            ->assertJsonPath('data.class_name', '資應');

        $appId = $storeResponse->json('data.id');

        // 2. 審核開通學生（需 Admin 權限）
        $adminToken = $this->loginToken('admin@nutc.edu.tw');

        $approveResponse = $this->withToken($adminToken)
            ->postJson("/api/v1/teacher/student-applications/{$appId}/approve");
        $approveResponse->assertOk()
            ->assertJsonPath('data.activated_count', 1);

        $student = Student::query()->where('student_no', '1411139001')->first();
        $this->assertNotNull($student);
        $this->assertSame('s1411139001@nutc.edu.tw', $student->email);

        // 3. 檢查通知信件包含正確新建人數與加密 Excel 附件
        Mail::assertSent(\App\Mail\StudentAccountCreated::class, function ($mail) {
            return $mail->studentCount === 1
                && !empty($mail->excelContent);
        });
    }

    public function test_non_admin_cannot_approve_student_application_batch(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'class_name' => '資應',
            'status' => 'pending',
        ]);

        StudentApplicationItems::query()->create([
            'application_id' => $application->id,
            'student_no' => '1411139999',
            'name' => '測試生',
            'status' => 'pending',
        ]);

        // 未登入 (401)
        $this->postJson("/api/v1/teacher/student-applications/{$application->id}/approve")
            ->assertUnauthorized();

        // 教師嘗試審核 (403)
        $teacherToken = $this->loginToken('teacher@school.edu.tw');
        $this->withToken($teacherToken)
            ->postJson("/api/v1/teacher/student-applications/{$application->id}/approve")
            ->assertForbidden();

        // 學生嘗試審核 (403)
        $studentToken = $this->loginToken('s1411131000');
        $this->withToken($studentToken)
            ->postJson("/api/v1/teacher/student-applications/{$application->id}/approve")
            ->assertForbidden();
    }

    public function test_teacher_can_download_student_roster_template(): void
    {
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->get('/api/v1/teacher/student-applications/template')
            ->assertOk()
            ->assertDownload('student_import_template.xlsx');
    }

    public function test_template_only_roster_rows_are_rejected(): void
    {
        $teacher = Teacher::query()->where('account', 'teacher2@school.edu.tw')->firstOrFail();
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();

        $this->post('/api/v1/teacher/student-applications', [
            'tid' => $teacher->id,
            'course_id' => $course->id,
            'file' => $this->upload(public_path('templates/student_import_template.xlsx')),
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function rosterXlsxPath(array $rows, bool $insertTemplateExample = true): string
    {
        array_unshift($rows, ['從學校名冊複製學號與姓名，貼在欄位列下方空白列。']);

        if ($insertTemplateExample) {
            foreach ($rows as $index => $row) {
                $hasNo = in_array('學號', $row, true);
                $hasName = in_array('姓名', $row, true);
                if ($hasNo && $hasName) {
                    array_splice($rows, $index + 1, 0, [['1411130000', '範例學生（不會匯入）']]);
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
        return new UploadedFile($path, 'roster.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
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
