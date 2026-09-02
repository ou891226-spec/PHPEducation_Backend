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
 * 審核並開通學生：可整單或勾選部分。
 */
class StudentApprovementController extends Controller
{
    public function __construct(
        private StudentAccountService $studentAccountService,
        private StudentCreateExcelService $excelService,
    ) {}

    /**
     * 管理員：開通勾選的學生（全選或幾個）。
     */
    public function approveSelected(ApproveStudentItemsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->studentAccountService->approveItems(
            (int) $validated['course_id'],
            $validated['item_ids'],
        );

        $this->notifyTeachers($result['created_by_teacher']);

        return response()->json([
            'message' => '已開通學生。',
            'activated_count' => $result['activated_count'],
            'created_count' => $result['created_count'],
            'enrolled_count' => $result['enrolled_count'],
        ]);
    }

    /**
     * 整張申請單一次開通尚未開通的人。
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

        // 做測試用
        $newStudents = collect($result['created_by_teacher'])->flatMap(fn ($group) => $group['students'])->values();

        return response()->json([
            'message' => 'Student account application approved.',
            'data' => [
                'application_id' => $application->id,
                'activated_count' => $result['activated_count'],
                'students' => $newStudents, // 測試用
            ],
        ]);
    }

    /**
     * @param  array<int, array{teacher_email: string, teacher_name: string, class_name: string, students: array<int, array<string, mixed>>}>  $createdByTeacher
     */
    private function notifyTeachers(array $createdByTeacher): void
    {
        foreach ($createdByTeacher as $group) {

            $studentCount = count($group['students'] ?? []);
        
            if ($group['students'] === []) {
                continue;
            }

            $excelContent = $this->excelService->generate(
                students: $group['students'],
                password: (string) ($group['teacher_account'] ?? ''), // 使用教師登入帳號作為 Excel 解鎖密碼
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
