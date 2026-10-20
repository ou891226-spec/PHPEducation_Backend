<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveStudentItemsRequest;
use App\Mail\StudentAccountCreated;
use App\Models\StudentApplications;
use App\Services\StudentAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * 學生帳號開通與審核控制器
 * 
 * 處理學生帳號申請單審核（支援批次勾選或整單審核），並寄送帳號密碼給新建帳號的學生。
 */
class StudentApprovementController extends Controller
{
    public function __construct(
        private StudentAccountService $studentAccountService,
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

        $this->notifyStudents($result['created_students']);

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

        $this->notifyStudents($result['created_students']);

        return response()->json([
            'message' => 'Student account application approved.',
            'data' => [
                'application_id' => $application->id,
                'activated_count' => $result['activated_count'],
                'created_count' => $result['created_count'],
            ],
        ]);
    }

    /**
     * 寄送帳號與初始密碼給新建帳號的學生
     *
     * 單封寄送失敗不影響開通結果，只記錄 log；學生可改用「忘記密碼」重設。
     *
     * @param  list<array{student_no: string, name: string, email: string, password: string, courses: list<array{name: string, teacher_name: string}>}>  $createdStudents
     * @return void
     */
    private function notifyStudents(array $createdStudents): void
    {
        foreach ($createdStudents as $student) {
            try {
                Mail::to($student['email'])->send(new StudentAccountCreated(
                    studentName: $student['name'],
                    studentAccount: $student['student_no'],
                    initialPassword: $student['password'],
                    courses: $student['courses'] ?? [],
                ));
            } catch (Throwable $exception) {
                Log::warning("學生帳號開通信寄送失敗：學號 {$student['student_no']}（{$student['email']}）", [
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
