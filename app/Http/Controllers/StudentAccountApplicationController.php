<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentAccountApplicationRequest;
use App\Models\StudentApplicationItems;
use App\Models\Teacher;
use App\Services\CourseService;
use App\Services\StudentAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 學生帳號申請：老師送出名單、依課程看待開通、管理員看全部明細。
 */
class StudentAccountApplicationController extends Controller
{
    public function __construct(
        private StudentAccountService $studentAccountService,
        private CourseService $courseService,
    ) {}

    /**
     * 管理員列表：每人一列。可加 ?status=pending。
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $items = StudentApplicationItems::query()
            ->with(['application.teacher'])
            ->when(
                is_string($status) && $status !== '',
                fn ($query) => $query->whereHas('application', fn ($application) => $application->where('status', $status)),
            )
            ->orderBy('id')
            ->get()
            ->map(fn (StudentApplicationItems $item) => [
                'id' => $item->id,
                'student_no' => $item->student_no,
                'name' => $item->name,
                'email' => $item->email,
                'application_id' => $item->application_id,
                'class_name' => $item->application?->class_name,
                'status' => $item->application?->status,
                'provider_teacher_name' => $item->application?->teacher?->name,
            ]);

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * 該課教師：依課程取得待開通名單。預設 pending；別人的課 404。
     */
    public function indexForCourse(Request $request, int $courseId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        $this->courseService->findForTeacher($user, $courseId);

        $status = $request->query('status', 'pending');

        $items = StudentApplicationItems::query()
            ->with(['application.teacher'])
            ->whereHas('application', function ($query) use ($user, $status) {
                $query->where('tid', $user->id);

                if (is_string($status) && $status !== '') {
                    $query->where('status', $status);
                }
            })
            ->orderBy('id')
            ->get()
            ->map(fn (StudentApplicationItems $item) => [
                'id' => $item->id,
                'student_no' => $item->student_no,
                'name' => $item->name,
                'email' => $item->email,
                'application_id' => $item->application_id,
                'class_name' => $item->application?->class_name,
                'status' => $item->application?->status,
                'provider_teacher_name' => $item->application?->teacher?->name,
            ]);

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * 教師送出整班學生申請。
     */
    public function store(StoreStudentAccountApplicationRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $application = $this->studentAccountService->createApplication(
            $validatedData['tid'],
            $validatedData,
        );

        return response()->json([
            'message' => 'Student account application submitted successfully.',
            'data' => $application,
        ], 201);
    }
    
}
