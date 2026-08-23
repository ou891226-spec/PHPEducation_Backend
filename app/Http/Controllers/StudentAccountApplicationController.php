<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentAccountApplicationRequest;
use App\Models\Student;
use App\Models\StudentApplicationItems;
use App\Models\Teacher;
use App\Services\CourseService;
use App\Services\StudentAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * 學生帳號申請：老師送出名單、依課程看待開通、管理員勾選開通。
 */
class StudentAccountApplicationController extends Controller
{
    public function __construct(
        private StudentAccountService $studentAccountService,
        private CourseService $courseService,
    ) {}

    /**
     * 管理員列表：每人一列。可加 ?course_id=、?status=pending、?q=。
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');
        $courseId = $request->query('course_id');
        $keyword = $request->query('q');

        $items = StudentApplicationItems::query()
            ->with(['application.teacher'])
            ->when(
                filled($courseId),
                fn ($query) => $query->whereHas(
                    'application',
                    fn ($application) => $application->where('course_id', $courseId),
                ),
            )
            ->when(
                is_string($status) && $status !== '',
                fn ($query) => $query->where('status', $status),
            )
            ->when(
                is_string($keyword) && $keyword !== '',
                fn ($query) => $query->where(function ($inner) use ($keyword) {
                    $inner->where('student_no', 'like', '%'.$keyword.'%')
                        ->orWhere('name', 'like', '%'.$keyword.'%');
                }),
            )
            ->orderBy('id')
            ->get();

        return response()->json([
            'items' => $this->formatItems($items),
        ]);
    }

    /**
     * 管理員開通頁的課程下拉。
     */
    public function courses(): JsonResponse
    {
        return response()->json([
            'courses' => $this->courseService->listAll(),
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
            ->whereHas('application', fn ($query) => $query->where('course_id', $courseId))
            ->when(
                is_string($status) && $status !== '',
                fn ($query) => $query->where('status', $status),
            )
            ->orderBy('id')
            ->get();

        return response()->json([
            'items' => $this->formatItems($items),
        ]);
    }

    /**
     * 教師送出整班學生申請。要帶 course_id。
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

    /**
     * @param  Collection<int, StudentApplicationItems>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function formatItems(Collection $items): Collection
    {
        $existingNos = Student::query()
            ->whereIn('student_no', $items->pluck('student_no')->filter()->all())
            ->pluck('student_no')
            ->all();

        $existing = array_flip($existingNos);

        return $items->map(fn (StudentApplicationItems $item) => [
            'id' => $item->id,
            'student_no' => $item->student_no,
            'name' => $item->name,
            'email' => Student::emailFromStudentNo($item->student_no),
            'application_id' => $item->application_id,
            'class_name' => $item->application?->class_name,
            'status' => $item->status,
            'course_id' => $item->application?->course_id,
            'provider_teacher_name' => $item->application?->teacher?->name,
            'has_account' => isset($existing[$item->student_no]),
        ]);
    }
}
