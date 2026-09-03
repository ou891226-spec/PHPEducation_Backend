<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\StudentForgotPasswordRequest;
use App\Http\Requests\Auth\TeacherForgotPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->string('account')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json($result);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => '登出成功',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->authService->me($request->user()),
        ]);
    }

    /**
     * 學生忘記密碼
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
}
