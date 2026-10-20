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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * 認證核心業務邏輯服務
 * 
 * 負責多身分使用者驗證、Token 發行與銷毀、密碼重設郵件發送及密碼修改。
 */
class AuthService
{
    public const RESET_TYPE_STUDENT = 'student';

    public const RESET_TYPE_TEACHER = 'teacher';

    public const RESET_LINK_MINUTES = 15;

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
     * 學生忘記密碼：寄重設密碼連結到學生綁定的信箱，密碼要等學生點連結設定後才會變更
     *
     * @param string $studentNo 學生學號或信箱
     * @return void
     * @throws ValidationException 當查無該學生時拋出
     */
    public function studentForgotPassword(string $studentNo): void
    {
        $studentNo = trim($studentNo);
        $normalized = $this->normalizedStudentNo($studentNo);

        $student = Student::query()
            ->where('student_no', $normalized)
            ->orWhere('student_no', $studentNo)
            ->orWhere('email', $studentNo)
            ->orWhere('email', Student::emailFromStudentNo($normalized))
            ->first();

        if ($student === null) {
            throw ValidationException::withMessages([
                'student_no' => ['學生帳號不存在'],
            ]);
        }

        Mail::to($student->email)->send(new StudentPasswordReset(
            studentName: $student->name,
            studentAccount: $student->student_no,
            resetUrl: $this->createResetUrl(self::RESET_TYPE_STUDENT, $student->id),
            expiresInMinutes: self::RESET_LINK_MINUTES,
        ));
    }

    /**
     * 教師忘記密碼：寄重設密碼連結到教師信箱，密碼要等教師點連結設定後才會變更
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

        Mail::to($teacher->email)->send(new TeacherPasswordReset(
            teacherName: $teacher->name,
            teacherAccount: $teacher->account,
            resetUrl: $this->createResetUrl(self::RESET_TYPE_TEACHER, $teacher->id),
            expiresInMinutes: self::RESET_LINK_MINUTES,
        ));
    }

    /**
     * 以重設連結的 token 設定新密碼
     *
     * 連結只能用一次；成功後登出該帳號所有裝置，舊密碼失效。
     *
     * @param string $type student 或 teacher
     * @param string $token 信中連結帶的原始 token
     * @param string $newPassword 新密碼
     * @return void
     * @throws ValidationException 當連結無效或已過期時拋出
     */
    public function resetPasswordWithToken(string $type, string $token, string $newPassword): void
    {
        DB::transaction(function () use ($type, $token, $newPassword) {
            $link = DB::table('password_reset_links')
                ->where('user_type', $type)
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            $user = null;
            if ($link !== null && now()->lessThanOrEqualTo($link->expires_at)) {
                $user = $type === self::RESET_TYPE_STUDENT
                    ? Student::query()->find($link->user_id)
                    : Teacher::query()->find($link->user_id);
            }

            if ($user === null) {
                throw ValidationException::withMessages([
                    'token' => ['重設連結無效或已過期，請重新申請忘記密碼'],
                ]);
            }

            $user->update(['password' => $newPassword]);
            $user->tokens()->delete();

            DB::table('password_reset_links')
                ->where('user_type', $type)
                ->where('user_id', $user->id)
                ->delete();
        });
    }

    /**
     * 產生一次性重設連結；同一帳號舊的連結會一併作廢
     */
    private function createResetUrl(string $type, int $userId): string
    {
        $token = Str::random(64);

        DB::transaction(function () use ($type, $userId, $token) {
            DB::table('password_reset_links')
                ->where('user_type', $type)
                ->where('user_id', $userId)
                ->delete();

            DB::table('password_reset_links')->insert([
                'user_type' => $type,
                'user_id' => $userId,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(self::RESET_LINK_MINUTES),
                'created_at' => now(),
            ]);
        });

        return rtrim((string) config('app.frontend_url'), '/')
            .'/reset-password?'.http_build_query(['type' => $type, 'token' => $token]);
    }

    /**
     * 根據帳號查詢對應的可認證主體 (Authenticatable)
     * 
     * 查詢順序：
     * 1. admins 資料表 (account 欄位)
     * 2. teachers 資料表 (account 欄位)
     * 3. students 資料表 (先比對學號，再比對 email；學生信箱可自訂，不一定是學號組成的校園信箱)
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

        if (! str_contains($account, '@')) {
            $student = Student::query()->where('student_no', ltrim($account, 'Ss'))->first();
            if ($student !== null) {
                return $student;
            }
        }

        return Student::query()->where('email', Student::emailFromStudentNo($account))->first();
    }

    private function normalizedStudentNo(string $input): string
    {
        if (str_contains($input, '@')) {
            $input = explode('@', $input)[0];
        }

        return ltrim($input, 'Ss');
    }
}
