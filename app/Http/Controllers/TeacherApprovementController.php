<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Services\TeacherAccountService;
use App\Models\TeacherApplication;
use App\Mail\TeacherAccountCreated;

/**
 * Class TeacherApprovementController
 * 負責處理管理員審核並開通教師帳號的控制器
 */
class TeacherApprovementController extends Controller
{

    public function __construct(
        private TeacherAccountService $teacherAccountService,
    ){}

    /**
     * 審核並批准教師申請
     *
     * 處理流程：
     * 1. 查找申請單並確認當前狀態為 pending（避免重複審核）
     * 2. 透過 Service 生成帳號密碼並建立 Teacher 實體
     * 3. 將申請單狀態更新為 approved
     * 4. 寄發開通信件給教師（包含帳號與明文初始密碼）
     *
     * @param int $id 申請單 ID
     * @return JsonResponse
     */
    public function approve(int $id)
    {
        $application = TeacherApplication::findOrFail($id);

        // 1. 查找申請單並確認當前狀態為 pending（避免重複審核）
        if($application->status !== 'pending') {
            return response()->json([
                'message' => 'This application has already been processed.',
            ], 422);
        }

        // 2. 透過 Service 生成帳號密碼並建立 Teacher 實體
        $teacher = $this->teacherAccountService->createFromApplication($application);

        // 3. 將申請單狀態更新為 approved
        $application->update([
                'status' => 'approved',
        ]);

        // 4. 寄發開通信件給教師（包含帳號與明文初始密碼）
        Mail::to($application->email)->send(new TeacherAccountCreated(
            tid: $teacher['tid'],
            name: $application->name,
            account: $teacher['account'],
            password: $teacher['password'],
        ));

        return response()->json([
            'message' => 'Teacher application approved.',
            'data' => [
                'tid' => $teacher['tid'],
                'name' => $application->name,
                'email' => $application->email,
                'account' => $teacher['account'],
                // 'password' => $teacher['password'],
            ],
        ], 200);
    }    
}
