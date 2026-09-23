<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseStudentRequest;
use App\Http\Requests\StoreStudentAccountApplicationRequest;
use App\Http\Requests\UpdateCourseStudentRequest;
use App\Models\Student;
use App\Models\StudentApplicationItems;
use App\Models\Teacher;
use App\Services\CourseService;
use App\Services\StudentAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * 學生帳號申請控制器
 * 
 * 處理教師提交學生名單（Excel 匯入或手動多筆）、依課程檢視學生名冊、移除學生，以及管理員檢視待審核申請項目。
 */
class StudentAccountApplicationController extends Controller
{
    public function __construct(
        private StudentAccountService $studentAccountService,
        private CourseService $courseService,
    ) {}

    /**
     * 管理員：取得所有學生帳號申請項目列表（支援多條件篩選）
     *
     * @param Request $request 支援 query parameters: course_id, status (預設 pending), q (搜尋姓名或學號)
     * @return JsonResponse
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
            ->orderBy('student_no')
            ->orderBy('id')
            ->get();

        return response()->json([
            'items' => $this->formatItems($items),
        ]);
    }

    /**
     * 管理員：取得所有課程列表（供審核頁面下拉選單使用）
     *
     * @return JsonResponse
     */
    public function courses(): JsonResponse
    {
        return response()->json([
            'courses' => $this->courseService->listAll(),
        ]);
    }

    /**
     * 該課程授課教師：取得該課程之學生名冊（包含待開通與已開通）
     * 
     * 若非該課程之授課教師將回傳 403 / 404
     *
     * @param Request $request 支援 query parameter: status (pending / approved)
     * @param int $courseId 課程 ID
     * @return JsonResponse
     */
    public function indexForCourse(Request $request, int $courseId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        $this->courseService->findForTeacher($user, $courseId);

        $status = $request->query('status');

        $items = StudentApplicationItems::query()
            ->with(['application.teacher'])
            ->whereHas('application', fn ($query) => $query->where('course_id', $courseId))
            ->when(
                is_string($status) && $status !== '',
                fn ($query) => $query->where('status', $status),
            )
            ->orderBy('student_no')
            ->orderBy('id')
            ->get();

        return response()->json([
            'items' => $this->formatItems($items),
        ]);
    }

    /**
     * 該課程授課教師：手動新增/補入學生（支援一次多筆）
     * 
     * 班級直接沿用課程所屬班級，提交後仍需由管理員審核開通（含已有帳號學生）。
     *
     * @param StoreCourseStudentRequest $request
     * @param int $courseId 課程 ID
     * @return JsonResponse
     */
    public function storeOneForCourse(StoreCourseStudentRequest $request, int $courseId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        $validated = $request->validated();

        $application = $this->studentAccountService->createApplication((string) $user->id, [
            'course_id' => $courseId,
            'students' => $validated['students'],
        ]);

        return response()->json([
            'message' => 'Student account application submitted successfully.',
            'data' => $application,
        ], 201);
    }

    /**
     * 該課程授課教師：依學號或姓名查詢是否已有帳號（自動帶入用）
     */
    public function lookupStudent(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        $studentNo = $request->query('student_no');
        $name = $request->query('name');

        return response()->json(
            $this->studentAccountService->lookupStudent(
                is_string($studentNo) ? $studentNo : null,
                is_string($name) ? $name : null,
            ),
        );
    }

    /**
     * 該課程授課教師：修改名冊中的一位學生（學號、姓名、信箱）
     *
     * pending 可改申請列信箱（開通時沿用）；approved 已有帳號時可改學號／信箱，姓名以帳號為準不覆寫。
     */
    public function updateForCourse(UpdateCourseStudentRequest $request, int $courseId, int $itemId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        $item = $this->studentAccountService->updateItemForCourse(
            $user,
            $courseId,
            $itemId,
            $request->validated(),
        );

        return response()->json([
            'message' => '學生資料已更新',
            'item' => $this->formatItems(collect([$item]))->first(),
        ]);
    }

    /**
     * 該課程授課教師：從課程名冊中移除學生
     * 
     * 若學生尚未審核開通則刪除申請明細；若已開通則解除課程選課（帳號仍保留）。
     *
     * @param Request $request
     * @param int $courseId 課程 ID
     * @param int $itemId 學生申請項目 ID
     * @return JsonResponse
     */
    public function destroyForCourse(Request $request, int $courseId, int $itemId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        $this->studentAccountService->removeItemForCourse($user, $courseId, $itemId);

        return response()->json([
            'message' => '已從課程移除',
        ]);
    }

    /**
     * 該課程授課教師：透過 Excel 檔案批次匯入整班學生名單申請
     *
     * @param StoreStudentAccountApplicationRequest $request
     * @return JsonResponse
     */
    public function store(StoreStudentAccountApplicationRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $path = $request->file('file')->getRealPath();

        $application = $this->studentAccountService->createApplicationFromExcel(
            (string) $validatedData['tid'],
            ['course_id' => (int) $validatedData['course_id']],
            $path,
        );

        return response()->json([
            'message' => 'Student account application submitted successfully.',
            'data' => $application,
        ], 201);
    }

    /**
     * 格式化學生申請項目集合，補齊帳號與教師資訊
     *
     * @param  Collection<int, StudentApplicationItems>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function formatItems(Collection $items): Collection
    {
        $studentsByNo = Student::query()
            ->whereIn('student_no', $items->pluck('student_no')->filter()->all())
            ->get(['student_no', 'name', 'email'])
            ->keyBy('student_no');

        return $items->map(function (StudentApplicationItems $item) use ($studentsByNo) {
            $account = $studentsByNo->get($item->student_no);
            $name = trim((string) $item->name);
            if ($name === '' && $account !== null) {
                $name = (string) $account->name;
            }

            $email = $account !== null && filled($account->email)
                ? (string) $account->email
                : (filled($item->email)
                    ? (string) $item->email
                    : Student::emailFromStudentNo((string) $item->student_no));

            return [
                'id' => $item->id,
                'student_no' => $item->student_no,
                'name' => $name,
                'email' => $email,
                'application_id' => $item->application_id,
                'class_name' => $item->application?->class_name,
                'status' => $item->status,
                'course_id' => $item->application?->course_id,
                'provider_teacher_name' => $item->application?->teacher?->name,
                'has_account' => $account !== null,
            ];
        });
    }
}
