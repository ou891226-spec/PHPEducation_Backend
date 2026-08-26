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

        $token = $this->loginToken('admin@school.edu.tw');

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
        $adminToken = $this->loginToken('admin@school.edu.tw');

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
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $existing = Student::query()->where('student_no', '1411131000')->firstOrFail();

        $application = StudentApplications::query()->create([
            'tid' => $teacher->id,
            'course_id' => $course->id,
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
        $course = Course::query()->where('name', '網際系統設計')->where('class_name', '資應')->firstOrFail();
        $token = $this->loginToken('teacher2@school.edu.tw');

        $this->withToken($token)
            ->postJson('/api/v1/student-applications/approve', [
                'course_id' => $course->id,
                'item_ids' => [1],
            ])
            ->assertForbidden();
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
        $adminToken = $this->loginToken('admin@school.edu.tw');

        $approveResponse = $this->withToken($adminToken)
            ->postJson("/api/v1/teacher/student-applications/{$appId}/approve");
        $approveResponse->assertOk()
            ->assertJsonPath('data.activated_count', 1);

        $student = Student::query()->where('student_no', '1411139001')->first();
        $this->assertNotNull($student);
        $this->assertSame('s1411139001@nutc.edu.tw', $student->email);

        // 3. 檢查通知信件包含正確明文密碼與信箱
        Mail::assertSent(\App\Mail\StudentAccountCreated::class, function ($mail) use ($student) {
            return $mail->students[0]['student_no'] === '1411139001'
                && $mail->students[0]['email'] === 's1411139001@nutc.edu.tw'
                && !empty($mail->students[0]['password']);
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
        $response = $this->postJson('/api/v1/auth/login', [
            'account' => $account,
            'password' => self::PASSWORD,
        ]);

        $response->assertOk();

        return $response->json('token');
    }
}
