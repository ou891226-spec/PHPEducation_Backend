<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Services\StudentAccountService;
use App\Models\StudentApplications;
use App\Mail\StudentAccountCreated;

/**
 * Class StudentApprovementController
 * 負責處理審核並批次開通學生帳號的控制器
 */
class StudentApprovementController extends Controller
{
    //
    public function __construct(
        private StudentAccountService $studentAccountService,
    ){}

    /**
     * 審核並批准學生帳號申請案件
     *
     * 處理流程：
     * 1. 查找申請主單，確認狀態為 pending
     * 2. 透過 Service 批次建立學生帳號（隨機產生密碼）
     * 3. 將申請單狀態更新為 approved
     * 4. 寄送信件將開通清單（含帳密）通知提出申請的教師
     *
     * @param int $id 學生申請單 ID
     * @return JsonResponse
     */
    public function approve(int $id)
    {
        $application = StudentApplications::findOrFail($id);

        // 1. 查找申請主單，確認狀態為 pending
        if($application->status !== 'pending') {
            return response()->json([
                'message' => 'This application has already been processed.',
            ], 422);
        }

        // 2. 透過 Service 批次建立學生帳號（隨機產生密碼）
        $studentData = $this->studentAccountService->approveApplication($application);

        // 3. 將申請單狀態更新為 approved
        $application->update([
            'status' => 'approved',
        ]);

        // 4. 寄送信件將開通清單（含帳密）通知提出申請的教師
        Mail::to($application->teacher->email)->send(new StudentAccountCreated(
            teacherName: $application->teacher->name,
            className: $application->class_name,
            students: $studentData,
        ));

        return response()->json([
            'message' => 'Student account application approved.',
            'data' => [
                'application_id' => $application->id,
                // 'students' => $studentData,
            ],
        ], 200);
    }
    
}
