<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreTeacherApplicationRequest;
use App\Models\TeacherApplication;
use App\Models\Teacher;

/**
 * Class TeacherApplicationController
 * 負責處理外部使用者提交教師帳號申請的控制器
 */
class TeacherApplicationController extends Controller
{
    /**
     * 提交教師帳號申請
     *
     * 驗證流程：
     * 1. 確認該 Email 是否已為正式教師
     * 2. 確認該 Email 是否已有尚未審核的申請案件（避免重複申請）
     * 3. 建立待審核（status: pending）的申請記錄
     *
     * @param StoreTeacherApplicationRequest $request
     * @return JsonResponse
     */
    public function store(StoreTeacherApplicationRequest $request)
    {
        $validatedData = $request->validated();

        // 1. 確認該 Email 是否已為正式教師
        $isTeacher = Teacher::where('email', $validatedData['email'])->exists();
        
        if ($isTeacher) {
            return response()->json([
                'message' => 'This email is already registered as a teacher.'
            ], 422);
        }
        
        // 2. 確認該 Email 是否已有尚未審核的申請案件（避免重複申請）
        $hasPendingApplication = TeacherApplication::where('email', $validatedData['email'])
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
            'reason' => $validatedData['reason'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Teacher application submitted successfully.',
            'data' => $teacherApplication,
        ], 201);
    }
}
