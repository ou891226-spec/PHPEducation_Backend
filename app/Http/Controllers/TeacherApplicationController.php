<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTeacherApplicationRequest;
use App\Models\TeacherApplication;
use App\Models\Teacher;

/**
 * 教師帳號申請控制器
 * 
 * 處理外部使用者提交教師申請，以及管理員檢視待審核申請清單。
 */
class TeacherApplicationController extends Controller
{
    /**
     * 管理員：取得教師帳號申請單列表
     *
     * @param Request $request 可透過 query parameter status (例如: ?status=pending) 進行狀態篩選
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $applications = TeacherApplication::query()
            ->when(is_string($status) && $status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('id')
            ->get()
            ->map(fn (TeacherApplication $application) => [
                'id' => $application->id,
                'name' => $application->name,
                'email' => $application->email,
                'account' => $application->account,
                'reason' => $application->reason,
                'status' => $application->status,
            ]);

        return response()->json([
            'applications' => $applications,
        ]);
    }

    /**
     * 外部使用者：提交教師帳號申請
     *
     * 業務驗證流程：
     * 1. 確認該 Email / Account 是否已存在於正式教師資料庫中（不可重複註冊）
     * 2. 確認該 Email / Account 是否已有待審核（status: pending）的申請案件（避免重複提交）
     * 3. 建立待審核的申請記錄
     *
     * @param StoreTeacherApplicationRequest $request
     * @return JsonResponse
     */
    public function store(StoreTeacherApplicationRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        // 1. 確認該 Email / Account 是否已為正式教師
        $isTeacher = Teacher::where('email', $validatedData['email'])
            ->orWhere('account', $validatedData['account'])
            ->exists();
        
        if ($isTeacher) {
            return response()->json([
                'message' => 'This email or account is already registered as a teacher.'
            ], 422);
        }
        
        // 2. 確認該 Email / Account 是否已有尚未審核的申請案件
        $hasPendingApplication = TeacherApplication::where(function ($query) use ($validatedData) {
                $query->where('email', $validatedData['email'])
                    ->orWhere('account', $validatedData['account']);
            })
            ->where('status', 'pending')
            ->exists();
            
        if ($hasPendingApplication) {
            return response()->json([
                'message' => 'You have a pending teacher application.'
            ], 422);
        }

        // 3. 建立待審核（status: pending）的申請記錄
        $teacherApplication = TeacherApplication::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'account' => $validatedData['account'],
            'reason' => $validatedData['reason'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Teacher application submitted successfully.',
            'data' => $teacherApplication,
        ], 201);
    }
}
