<?php

namespace Tests\Feature;

use App\Mail\StudentPasswordReset;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\CourseService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    private const PASSWORD = DatabaseSeeder::TEST_PASSWORD;

    public function test_admin_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'account' => 'admin@nutc.edu.tw',
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
            'class_name' => '資應二甲',
        ]);

        $create->assertCreated()
            ->assertJsonPath('course.name', 'PHP 程式設計')
            ->assertJsonPath('course.description', '從基礎語法到實作練習')
            ->assertJsonPath('course.class_name', '資應二甲');

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
                'class_name' => '資應二甲',
            ])
            ->assertOk()
            ->assertJsonPath('course.name', 'PHP 進階')
            ->assertJsonPath('course.description', '進階主題與專案實作')
            ->assertJsonPath('course.class_name', '資應二甲');

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
                'description' => '非法修改',
                'semester' => '115-1',
                'class_name' => '非法班級',
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
        $token = $this->loginToken('admin@nutc.edu.tw');

        $this->withToken($token)
            ->getJson('/api/v1/teacher/courses')
            ->assertForbidden();
    }
    
    // 
    public function test_student_forgot_password_sends_link_and_keeps_old_password(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/student/forgot-password', [
            'student_no' => '1411131000',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', '已寄送重設密碼連結至學生信箱');

        Mail::assertSent(StudentPasswordReset::class, function (StudentPasswordReset $mail) {
            return $mail->studentAccount === '1411131000'
                && str_starts_with($mail->resetUrl, 'http://localhost:9000/reset-password?type=student&token=')
                && $mail->expiresInMinutes === 15
                && str_contains($mail->render(), 'href="'.e($mail->resetUrl).'"');
        });

        $this->postJson('/api/v1/auth/login', [
            'account' => '1411131000',
            'password' => self::PASSWORD,
        ])->assertOk();
    }

    public function test_student_with_custom_email_can_reset_password_with_link(): void
    {
        Mail::fake();

        Student::query()->create([
            'student_no' => '0123456789',
            'name' => '自訂信箱同學',
            'email' => 'custom.student@gmail.com',
            'password' => 'unknown-initial-password',
        ]);

        $this->postJson('/api/v1/auth/student/forgot-password', [
            'student_no' => 'custom.student@gmail.com',
        ])->assertOk();

        $token = null;
        Mail::assertSent(StudentPasswordReset::class, function (StudentPasswordReset $mail) use (&$token) {
            $token = $this->tokenFromUrl($mail->resetUrl);

            return $mail->hasTo('custom.student@gmail.com') && $mail->studentAccount === '0123456789';
        });

        $this->postJson('/api/v1/auth/reset-password', [
            'type' => 'student',
            'token' => $token,
            'new_password' => 'MyNewPassword1',
            'new_password_confirmation' => 'MyNewPassword1',
        ])
            ->assertOk()
            ->assertJsonPath('message', '密碼已重設，請使用新密碼登入');

        foreach (['0123456789', 's0123456789', 'custom.student@gmail.com'] as $account) {
            $this->postJson('/api/v1/auth/login', [
                'account' => $account,
                'password' => 'MyNewPassword1',
            ])
                ->assertOk()
                ->assertJsonPath('user.role', 'student')
                ->assertJsonPath('user.student_no', '0123456789');
        }

        $this->postJson('/api/v1/auth/login', [
            'account' => '0123456789',
            'password' => 'unknown-initial-password',
        ])->assertUnauthorized();

        // 同一個連結不能再用
        $this->postJson('/api/v1/auth/reset-password', [
            'type' => 'student',
            'token' => $token,
            'new_password' => 'AnotherPassword1',
            'new_password_confirmation' => 'AnotherPassword1',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_reset_link_expires_after_15_minutes(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/student/forgot-password', [
            'student_no' => '1411131000',
        ])->assertOk();

        $token = null;
        Mail::assertSent(StudentPasswordReset::class, function (StudentPasswordReset $mail) use (&$token) {
            $token = $this->tokenFromUrl($mail->resetUrl);

            return true;
        });

        $this->travel(16)->minutes();

        $this->postJson('/api/v1/auth/reset-password', [
            'type' => 'student',
            'token' => $token,
            'new_password' => 'MyNewPassword1',
            'new_password_confirmation' => 'MyNewPassword1',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_new_forgot_request_invalidates_previous_link(): void
    {
        Mail::fake();

        $tokens = [];
        foreach ([1, 2] as $_) {
            $this->postJson('/api/v1/auth/student/forgot-password', [
                'student_no' => '1411131000',
            ])->assertOk();
        }

        Mail::assertSent(StudentPasswordReset::class, function (StudentPasswordReset $mail) use (&$tokens) {
            $tokens[] = $this->tokenFromUrl($mail->resetUrl);

            return true;
        });

        $payload = [
            'type' => 'student',
            'new_password' => 'MyNewPassword1',
            'new_password_confirmation' => 'MyNewPassword1',
        ];

        $this->postJson('/api/v1/auth/reset-password', $payload + ['token' => $tokens[0]])
            ->assertStatus(422);
        $this->postJson('/api/v1/auth/reset-password', $payload + ['token' => $tokens[1]])
            ->assertOk();
    }

    public function test_student_token_cannot_reset_teacher_password(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/student/forgot-password', [
            'student_no' => '1411131000',
        ])->assertOk();

        $token = null;
        Mail::assertSent(StudentPasswordReset::class, function (StudentPasswordReset $mail) use (&$token) {
            $token = $this->tokenFromUrl($mail->resetUrl);

            return true;
        });

        $this->postJson('/api/v1/auth/reset-password', [
            'type' => 'teacher',
            'token' => $token,
            'new_password' => 'MyNewPassword1',
            'new_password_confirmation' => 'MyNewPassword1',
        ])->assertStatus(422);
    }

    public function test_student_forgot_password_accepts_school_email_aliases(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/student/forgot-password', [
            'student_no' => 's1411131000',
        ])->assertOk();

        $this->postJson('/api/v1/auth/student/forgot-password', [
            'student_no' => '1411131000@nutc.edu.tw',
        ])->assertOk();
    }

    public function test_student_forgot_password_with_invalid_student_no(): void
    {
        $response = $this->postJson('/api/v1/auth/student/forgot-password', [
            'student_no' => 'invalid_student_no',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_no']);
    }

    public function test_teacher_forgot_password_sends_link_and_can_reset(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/teacher/forgot-password', [
            'teacher_account' => 'teacher@school.edu.tw',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', '已寄送重設密碼連結至教師信箱');

        $token = null;
        Mail::assertSent(\App\Mail\TeacherPasswordReset::class, function ($mail) use (&$token) {
            $token = $this->tokenFromUrl($mail->resetUrl);

            return $mail->teacherAccount === 'teacher@school.edu.tw'
                && str_contains($mail->resetUrl, 'type=teacher');
        });

        $this->loginToken('teacher@school.edu.tw');

        $this->postJson('/api/v1/auth/reset-password', [
            'type' => 'teacher',
            'token' => $token,
            'new_password' => 'TeacherNew123',
            'new_password_confirmation' => 'TeacherNew123',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'account' => 'teacher@school.edu.tw',
            'password' => 'TeacherNew123',
        ])->assertOk();
    }

    private function tokenFromUrl(string $url): string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return (string) $query['token'];
    }

    public function test_teacher_forgot_password_with_invalid_account(): void
    {
        $response = $this->postJson('/api/v1/auth/teacher/forgot-password', [
            'teacher_account' => 'nonexistent_teacher',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['teacher_account']);
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

    // 
    public function test_teacher_can_change_password_and_login_with_new_password(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $response = $this->withToken($token)->postJson('/api/v1/auth/change-password', [
            'current_password' => self::PASSWORD,
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', '密碼修改成功');

        // 驗證舊密碼已無法登入
        $oldLogin = $this->postJson('/api/v1/auth/login', [
            'account' => 'teacher@school.edu.tw',
            'password' => self::PASSWORD,
        ]);
        $oldLogin->assertStatus(401);

        // 驗證新密碼可以正常登入
        $newLogin = $this->postJson('/api/v1/auth/login', [
            'account' => 'teacher@school.edu.tw',
            'password' => 'NewPassword123!',
        ]);
        $newLogin->assertOk()
            ->assertJsonPath('user.role', 'teacher');
    }

    public function test_student_can_change_password(): void
    {
        $token = $this->loginToken('s1411131000');

        $response = $this->withToken($token)->postJson('/api/v1/auth/change-password', [
            'current_password' => self::PASSWORD,
            'new_password' => 'NewStudentPass123',
            'new_password_confirmation' => 'NewStudentPass123',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', '密碼修改成功');

        // 驗證資料庫確實更新了密碼
        $student = \App\Models\Student::where('student_no', '1411131000')->firstOrFail();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewStudentPass123', $student->password));

        // 驗證舊密碼已無法登入
        $oldLogin = $this->postJson('/api/v1/auth/login', [
            'account' => 's1411131000',
            'password' => self::PASSWORD,
        ]);
        $oldLogin->assertStatus(401);

        // 驗證新密碼可以正常登入
        $newLogin = $this->postJson('/api/v1/auth/login', [
            'account' => 's1411131000',
            'password' => 'NewStudentPass123',
        ]);
        $newLogin->assertOk()
            ->assertJsonPath('user.role', 'student');
    }


    public function test_change_password_with_wrong_current_password_fails(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $response = $this->withToken($token)->postJson('/api/v1/auth/change-password', [
            'current_password' => 'WrongCurrentPassword',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_change_password_with_unmatched_confirmation_fails(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $response = $this->withToken($token)->postJson('/api/v1/auth/change-password', [
            'current_password' => self::PASSWORD,
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'DifferentPassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_change_password_with_same_as_current_fails(): void
    {
        $token = $this->loginToken('teacher@school.edu.tw');

        $response = $this->withToken($token)->postJson('/api/v1/auth/change-password', [
            'current_password' => self::PASSWORD,
            'new_password' => self::PASSWORD,
            'new_password_confirmation' => self::PASSWORD,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }
}
