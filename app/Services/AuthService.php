<?php

namespace App\Services;
use App\Mail\StudentPasswordReset;
use App\Mail\TeacherPasswordReset;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Exceptions\UnauthorizedLoginException;
use App\Models\Admin;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly UserFormatterService $userFormatter,
    ) {}

    public function login(string $account, string $password): array
    {
        $authenticatable = $this->findAuthenticatable($account);

        if ($authenticatable === null || ! Hash::check($password, $authenticatable->getAuthPassword())) {
            throw new UnauthorizedLoginException();
        }

        $token = $authenticatable->createToken('auth')->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userFormatter->format($authenticatable),
        ];
    }

    public function logout(Authenticatable $authenticatable): void
    {
        $token = $authenticatable->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }
    }

    public function me(Authenticatable $authenticatable): array
    {
        return $this->userFormatter->format($authenticatable);
    }

    /**
     * 學生忘記密碼：重設隨機新密碼並寄信給學生校園信箱
     */
    public function studentForgotPassword(string $studentNo): void
    {
        $student = Student::query()
            ->where('student_no', $studentNo)
            ->orwhere('email', Student::emailFromStudentNo($studentNo))
            ->first();

        if ($student === null) {
            throw ValidationException::withMessages([
                'student_no' => ['學生帳號不存在'],
            ]);
        }

        $newPassword = Str::random(12);
        $student->update([
            'password' => $newPassword,
        ]);

        Mail::to($student->email)->send(new StudentPasswordReset(
            studentName: $student->name,
            studentAccount: $student->student_no,
            newPassword: $newPassword,
        ));
    }

    /**
     * 教師忘記密碼：重設隨機新密碼並寄信給教師信箱
     */
    public function teacherForgotPassword(string $teacherAccount): void
    {
        $teacher = Teacher::query()
            ->where('account', $teacherAccount)
            ->first();

        if ($teacher === null) {
            throw ValidationException::withMessages([
                'teacher_account' => ['教師帳號不存在'],
            ]);
        }

        $newPassword = Str::random(12);
        $teacher->update([
            'password' => $newPassword,
        ]);

        Mail::to($teacher->email)->send(new TeacherPasswordReset(
            teacherName: $teacher->name,
            teacherAccount: $teacher->account,
            newPassword: $newPassword,
        ));
    }


    /**
     * Lookup order: admins.account → teachers.account → students.email
     * Students type student no (e.g. s1411131000); backend stores school email.
     */
    private function findAuthenticatable(string $account): ?Authenticatable
    {
        $admin = Admin::query()->where('account', $account)->first();
        if ($admin !== null) {
            return $admin;
        }

        $teacher = Teacher::query()->where('account', $account)->first();
        if ($teacher !== null) {
            return $teacher;
        }

        return Student::query()->where('email', Student::emailFromStudentNo($account))->first();
    }
}
