<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveStudentItemsRequest;
use App\Mail\StudentAccountCreated;
use App\Models\StudentApplications;
use App\Services\StudentAccountService;
use App\Services\StudentCreateExcelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

/**
 * 學生帳號開通與審核控制器
 * 
 * 處理學生帳號申請單審核（支援批次勾選或整單審核），並產製加密 Excel 檔發信通知教師。
 */
class StudentApprovementController extends Controller
{
    public function __construct(
        private StudentAccountService $studentAccountService,
        private StudentCreateExcelService $excelService,
    ) {}

    /**
     * 管理員：開通勾選的學生，並寫入一門或多門課程選課
     *
     * @param ApproveStudentItemsRequest $request
     * @return JsonResponse
     */
    public function approveSelected(ApproveStudentItemsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $courseIds = $validated['course_ids'];

        if (! empty($validated['source_course_id']) && empty($validated['item_ids'])) {
            $result = $this->studentAccountService->approvePendingForSourceCourse(
                (int) $validated['source_course_id'],
                $courseIds,
            );
        } else {
            $result = $this->studentAccountService->approveItems(
                $courseIds,
                $validated['item_ids'],
            );
        }

        $this->notifyTeachers($result['created_by_teacher']);

        return response()->json([
            'message' => '已開通課程。',
            'activated_count' => $result['activated_count'],
            'created_count' => $result['created_count'],
            'enrolled_count' => $result['enrolled_count'],
        ]);
    }

    /**
     * 整張申請單一次審核開通尚未開通的學生
     *
     * @param int $id 學生申請單 ID
     * @return JsonResponse
     */
    public function approve(int $id): JsonResponse
    {
        $application = StudentApplications::findOrFail($id);

        if ($application->status !== 'pending' && $application->items()->where('status', 'pending')->doesntExist()) {
            return response()->json([
                'message' => 'This application has already been processed.',
            ], 422);
        }

        $result = $this->studentAccountService->approveApplication($application);

        $this->notifyTeachers($result['created_by_teacher']);

        // 整理本次新增的學生清單（供測試案例斷言驗證）
        $newStudents = collect($result['created_by_teacher'])->flatMap(fn ($group) => $group['students'])->values();

        return response()->json([
            'message' => 'Student account application approved.',
            'data' => [
                'application_id' => $application->id,
                'activated_count' => $result['activated_count'],
                'students' => $newStudents,
            ],
        ]);
    }

    /**
     * 產製加密 Excel 檔案並以電子郵件通知相關教師
     *
     * @param  array<int, array{teacher_account: string, teacher_email: string, teacher_name: string, class_name: string, course_name: string, students: array<int, array<string, mixed>>}>  $createdByTeacher
     * @return void
     */
    private function notifyTeachers(array $createdByTeacher): void
    {
        foreach ($createdByTeacher as $group) {
            $studentCount = count($group['students'] ?? []);
        
            // 若該教師組別沒有新建立的學生，則不產製附件亦不寄送郵件
            if ($group['students'] === []) {
                continue;
            }

            // 以教師帳號作為密碼加密 Excel
            $excelContent = $this->excelService->generate(
                students: $group['students'],
                password: (string) ($group['teacher_account'] ?? ''),
            );

            Mail::to($group['teacher_email'])->send(new StudentAccountCreated(
                teacherName: $group['teacher_name'],
                courseName: (string) ($group['course_name'] ?? ''),
                className: $group['class_name'] ?? '',
                studentCount: $studentCount,
                excelContent: $excelContent,
            ));
        }
    }
}
