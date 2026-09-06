<?php
namespace App\Services;
use App\Models\StudentApplications;
use App\Models\StudentApplicationItems;
use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Teacher;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * 學生帳號業務服務
 * 
 * 專責處理學生帳號申請單建立（手動或 Excel）、名冊移除，以及批次審核開通與選課邏輯。
 */
class StudentAccountService
{
    public function __construct(
        private readonly ExcelStudentRosterParser $rosterParser,
    ) {}

    /**
     * 解析 Excel 名冊並建立學生帳號申請單（含明細項目）
     * 
     * 班級直接沿用課程設定之 class_name，不需自 Excel 中讀取。
     *
     * @param string $tid 授課教師 ID
     * @param array{course_id: int} $data 課程資料
     * @param string $path 上傳之 Excel 檔案實體路徑
     * @return StudentApplications
     * @throws ValidationException 當 Excel 格式錯誤、無資料或超過 100 筆時拋出
     */
    public function createApplicationFromExcel(string $tid, array $data, string $path): StudentApplications
    {
        try {
            $students = $this->rosterParser->parse($path);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'file' => [$exception->getMessage()],
            ]);
        }

        if ($students === []) {
            throw ValidationException::withMessages([
                'file' => ['沒有可匯入的學生列（第 3 列範本不讀）'],
            ]);
        }

        if (count($students) > 100) {
            throw ValidationException::withMessages([
                'file' => ['一次最多匯入 100 位學生'],
            ]);
        }

        return $this->createApplication($tid, [
            'course_id' => $data['course_id'],
            'students' => $students,
        ]);
    }

    /**
     * 建立學生帳號申請單（含明細項目）
     *
     * 驗證規則：
     * 1. 課程必須設定班級名稱 (class_name)
     * 2. 單次申請最多 100 位學生
     * 3. 提交的學號不可重複
     * 4. 該課程下不可有重複學號的申請中項目
     *
     * @param string $tid 授課教師 ID
     * @param array{course_id: int, students: list<array{student_no: string, name: string}>} $data 申請資料
     * @return StudentApplications
     * @throws ValidationException 當驗證未通過時拋出
     */
    public function createApplication(string $tid, array $data): StudentApplications
    {
        return DB::transaction(function () use ($tid, $data) {
            $course = Course::query()
                ->whereKey($data['course_id'])
                ->where('teacher_id', $tid)
                ->firstOrFail();

            $className = trim((string) $course->class_name);
            if ($className === '') {
                throw ValidationException::withMessages([
                    'course_id' => ['請先在課程填寫班級'],
                ]);
            }

            if (count($data['students']) > 100) {
                throw ValidationException::withMessages([
                    'file' => ['一次最多匯入 100 位學生'],
                    'students' => ['一次最多匯入 100 位學生'],
                ]);
            }

            $studentNos = array_column($data['students'], 'student_no');
            if (count($studentNos) !== count(array_unique($studentNos))) {
                throw ValidationException::withMessages([
                    'file' => ['學號重複'],
                    'students' => ['學號重複'],
                ]);
            }

            $exists = StudentApplicationItems::query()
                ->whereIn('student_no', $studentNos)
                ->whereHas(
                    'application',
                    fn ($query) => $query->where('course_id', $course->id),
                )
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'file' => ['該課已有相同學號'],
                    'student_no' => ['該課已有相同學號'],
                    'students' => ['該課已有相同學號'],
                ]);
            }

            $application = StudentApplications::create([
                'tid' => $tid,
                'course_id' => $course->id,
                'class_name' => $className,
                'status' => 'pending',
            ]);

            foreach ($data['students'] as $studentData) {
                StudentApplicationItems::create([
                    'application_id' => $application->id,
                    'student_no' => $studentData['student_no'],
                    'name' => $studentData['name'],
                    'status' => 'pending',
                ]);
            }

            return $application;
        });
    }

    /**
     * 授課教師從課程名冊中移除學生
     * 
     * - 若為「待審核 (pending)」狀態：直接刪除申請項目明細列。
     * - 若為「已核准 (approved)」狀態：取消該學生在此課程的選課 (Enrollment)，但保留學生帳號。
     * - 若主申請單底下已無明細項目，則連同主單一併刪除。
     *
     * @param Teacher $teacher 授課教師
     * @param int $courseId 課程 ID
     * @param int $itemId 學生申請項目 ID
     * @return void
     */
    public function removeItemForCourse(Teacher $teacher, int $courseId, int $itemId): void
    {
        DB::transaction(function () use ($teacher, $courseId, $itemId) {
            $course = Course::query()
                ->whereKey($courseId)
                ->where('teacher_id', $teacher->id)
                ->firstOrFail();

            $item = StudentApplicationItems::query()
                ->with('application')
                ->whereKey($itemId)
                ->whereHas(
                    'application',
                    fn ($query) => $query->where('course_id', $course->id),
                )
                ->firstOrFail();

            // 若為「待審核 (pending)」狀態：直接刪除申請項目明細列。
            if ($item->status === 'approved') {
                $student = Student::query()->where('student_no', $item->student_no)->first();
                if ($student !== null) {
                    Enrollment::query()
                        ->where('student_id', $student->id)
                        ->where('course_id', $course->id)
                        ->delete();
                }
            }

            // 若主申請單底下已無明細項目，則連同主單一併刪除。
            $application = $item->application;
            $item->delete();

            if ($application === null) {
                return;
            }

            if ($application->items()->doesntExist()) {
                $application->delete();

                return;
            }

            // 若主單底下仍有明細項目，且所有明細項目皆已核准，則將主單狀態更新為 approved。
            if (! $application->items()->where('status', 'pending')->exists()) {
                $application->update(['status' => 'approved']);
            }
        });
    }

    /**
     * 管理員：批次審核開通勾選的學生項目
     * 
     * 處理邏輯：
     * 1. 若學生帳號不存在，自動建立 Student 實體與初始密碼（累計至通知教師清單）。
     * 2. 若學生尚未選修該課程，自動建立 Enrollment 選課關聯。
     * 3. 更新申請項目狀態為 approved，若主單所有項目皆已審核，將主單狀態亦更新為 approved。
     *
     * @param int $courseId 課程 ID
     * @param array<int, int> $itemIds 欲開通的申請項目 ID 陣列
     * @return array{activated_count: int, created_count: int, enrolled_count: int, created_by_teacher: array<int, array{course_name: string, teacher_account: string, teacher_email: string, teacher_name: string, class_name: string, students: array<int, array{sid: int, class_name: string, student_no: string, name: string, password: string, email: string}>}>}
     * @throws ValidationException 當項目不存在、已開通或不屬於該課程時拋出
     */
    public function approveItems(int $courseId, array $itemIds): array
    {
        $course = Course::query()->findOrFail($courseId);
        $uniqueIds = array_values(array_unique(array_map('intval', $itemIds)));

        return DB::transaction(function () use ($course, $uniqueIds) {
            $items = StudentApplicationItems::query()
                ->with('application.teacher')
                ->whereIn('id', $uniqueIds)
                ->where('status', 'pending')
                ->whereHas('application', fn ($query) => $query->where('course_id', $course->id))
                ->get();

            if ($items->count() !== count($uniqueIds)) {
                throw ValidationException::withMessages([
                    'item_ids' => ['部分學生不存在、已開通，或不屬於這門課。'],
                ]);
            }

            $createdByTeacher = [];
            $createdCount = 0;

            // 若學生帳號不存在，自動建立 Student 實體與初始密碼（累計至通知教師清單）。
            foreach ($items as $item) {
                $student = Student::query()
                    ->where('student_no', $item->student_no)
                    ->first();

                $plainPassword = null;
                $email = Student::emailFromStudentNo($item->student_no);

                if ($student === null) {
                    $plainPassword = $this->generatePassword();

                    $student = Student::create([
                        'class_name' => $item->application?->class_name,
                        'student_no' => $item->student_no,
                        'name' => $item->name,
                        'password' => $plainPassword,
                        'email' => $email,
                    ]);

                    $createdCount++;
                }

                // 若學生尚未選修該課程，自動建立 Enrollment 選課關聯。
                $alreadyEnrolled = Enrollment::query()
                    ->where('student_id', $student->id)
                    ->where('course_id', $course->id)
                    ->exists();

                if (! $alreadyEnrolled) {
                    Enrollment::query()->create([
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                    ]);
                }

                $item->update(['status' => 'approved']);

                // 若主單所有項目皆已審核，將主單狀態亦更新為 approved。
                $application = $item->application;
                if ($application !== null && ! $application->items()->where('status', 'pending')->exists()) {
                    $application->update(['status' => 'approved']);
                }

                if ($plainPassword !== null && $application?->teacher !== null) {
                    $teacherId = $application->tid;
                    $createdByTeacher[$teacherId] ??= [
                        'course_name' => $course->name, // 取得當前課程
                        'teacher_account' => $application->teacher->account, // 取得教師登入帳號 (作為解鎖 Excel 密碼)
                        'teacher_email' => $application->teacher->email,
                        'teacher_name' => $application->teacher->name,
                        'class_name' => $application->class_name,
                        'students' => [],
                    ];
                    $createdByTeacher[$teacherId]['students'][] = [
                        'sid' => $student->id,
                        'class_name' => $application->class_name,
                        'student_no' => $student->student_no,
                        'name' => $item->name,
                        'password' => $plainPassword,
                        'email' => $email,
                    ];
                }
            }

            return [
                'activated_count' => $items->count(),
                'created_count' => $createdCount,
                'enrolled_count' => $items->count(),
                'created_by_teacher' => array_values($createdByTeacher),
            ];
        });
    }

    /**
     * 管理員：將整張申請單中所有尚未開通的學生一次審核開通
     *
     * @param StudentApplications $application 學生帳號申請主單
     * @return array{activated_count: int, created_count: int, enrolled_count: int, created_by_teacher: array<int, mixed>}
     * @throws ValidationException 當無課程或無待開通項目時拋出
     */
    public function approveApplication(StudentApplications $application): array
    {
        if ($application->course_id === null) {
            throw ValidationException::withMessages([
                'course_id' => ['這張申請單沒有課程，無法開通。'],
            ]);
        }

        $itemIds = $application->items()
            ->where('status', 'pending')
            ->pluck('id')
            ->all();

        if ($itemIds === []) {
            throw ValidationException::withMessages([
                'items' => ['沒有可開通的學生。'],
            ]);
        }

        return $this->approveItems((int) $application->course_id, $itemIds);
    }

    /**
     * 生成 12 碼隨機英數字串作為學生初始預設密碼
     *
     * @return string
     */
    private function generatePassword(): string
    {
        return Str::random(12);
    }
}
