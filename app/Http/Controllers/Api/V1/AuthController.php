<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\StudentForgotPasswordRequest;
use App\Http\Requests\Auth\TeacherForgotPasswordRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 認證與授權控制器
 * 
 * 處理全站使用者（管理者、教師、學生）的登入、登出、目前資訊查詢、忘記密碼與密碼修改。
 */     
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * 使用者登入
     * 
     * 支援 Admin、Teacher 與 Student，登入成功後回傳 Sanctum Bearer Token 與使用者基本資料。
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->string('account')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json($result);
    }

    /**
     * 使用者登出
     * 
     * 撤銷當前存取的 Personal Access Token。
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => '登出成功',
        ]);
    }

    /**
     * 取得當前已登入使用者個人資訊
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->authService->me($request->user()),
        ]);
    }

    /**
     * 學生忘記密碼
     * 
     * 產生隨機新密碼並發送通知信至學生之校園信箱 (s[學號]@nutc.edu.tw)。
     *
     * @param StudentForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function studentForgotPassword(StudentForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->studentForgotPassword(
            $request->string('student_no')->toString(),
        );

        return response()->json([
            'message' => '已寄送新密碼至學生校園信箱',
        ]);
    }

    /**
     * 教師忘記密碼
     * 
     * 產生隨機新密碼並發送通知信至教師登記信箱。
     *
     * @param TeacherForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function teacherForgotPassword(TeacherForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->teacherForgotPassword(
            $request->string('teacher_account')->toString(),
        );

        return response()->json([
            'message' => '已寄送新密碼至教師校園信箱',
        ]);
    }

    /**
     * 已登入使用者修改密碼
     * 
     * 需提供舊密碼做身分驗證，驗證成功後將密碼更新為新密碼。
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword(
            $request->user(),
            $request->string('current_password')->toString(),
            $request->string('new_password')->toString(),
        );

        return response()->json([
            'message' => '密碼修改成功',
        ]);
    }
}
