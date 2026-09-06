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

/**
 * 認證核心業務邏輯服務
 * 
 * 負責多身分使用者驗證、Token 發行與銷毀、密碼重設郵件發送及密碼修改。
 */
class AuthService
{
    public function __construct(
        private readonly UserFormatterService $userFormatter,
    ) {}

    /**
     * 執行使用者登入驗證
     *
     * @param string $account 登入帳號（管理者帳號 / 教師帳號 / 學生學號或信箱）
     * @param string $password 明文密碼
     * @return array{token: string, token_type: string, user: array}
     * @throws UnauthorizedLoginException 當帳號不存在或密碼錯誤時拋出
     */
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

    /**
     * 登出並刪除當前使用的 Access Token
     *
     * @param Authenticatable $authenticatable
     * @return void
     */
    public function logout(Authenticatable $authenticatable): void
    {
        $token = $authenticatable->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }
    }

    /**
     * 格式化並回傳當前登入者資訊
     *
     * @param Authenticatable $authenticatable
     * @return array
     */
    public function me(Authenticatable $authenticatable): array
    {
        return $this->userFormatter->format($authenticatable);
    }

    /**
     * 已登入使用者修改密碼（通用於 Admin, Teacher, Student）
     *
     * @param Authenticatable $user 當前登入的使用者實例
     * @param string $currentPassword 使用者輸入的舊密碼
     * @param string $newPassword 使用者設定的新密碼
     * @return void
     * @throws ValidationException 當目前密碼驗證錯誤時拋出
     */
    public function changePassword(Authenticatable $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => ['目前密碼不正確'],
            ]);
        }

        // Eloquent Model 內建 'hashed' cast 會自動處理雜湊
        $user->update([
            'password' => $newPassword,
        ]);
    }

    /**
     * 學生忘記密碼：重設隨機新密碼並寄信給學生校園信箱
     *
     * @param string $studentNo 學生學號
     * @return void
     * @throws ValidationException 當查無該學生時拋出
     */
    public function studentForgotPassword(string $studentNo): void
    {
        $student = Student::query()
            ->where('student_no', $studentNo)
            ->orWhere('email', Student::emailFromStudentNo($studentNo))
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
     *
     * @param string $teacherAccount 教師登入帳號
     * @return void
     * @throws ValidationException 當查無該教師時拋出
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
     * 根據帳號查詢對應的可認證主體 (Authenticatable)
     * 
     * 查詢順序：
     * 1. admins 資料表 (account 欄位)
     * 2. teachers 資料表 (account 欄位)
     * 3. students 資料表 (透過學號轉成校園 email 比對)
     *
     * @param string $account 登入識別字串
     * @return Authenticatable|null
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
