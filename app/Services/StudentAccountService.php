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
     * 行為：
     * - 已有學生帳號：以帳號姓名為準寫入（前端可自動帶入），申請列直接 approved，並立刻寫入本課選課
     * - 尚無帳號：申請列 pending，等管理員開通後才建帳／選課
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

            $existingStudents = Student::query()
                ->whereIn('student_no', $studentNos)
                ->get()
                ->keyBy('student_no');

            $pendingCount = 0;
            foreach ($data['students'] as $studentData) {
                if (! $existingStudents->has($studentData['student_no'])) {
                    $pendingCount++;
                }
            }

            $application = StudentApplications::create([
                'tid' => $tid,
                'course_id' => $course->id,
                'class_name' => $className,
                'status' => $pendingCount > 0 ? 'pending' : 'approved',
            ]);

            foreach ($data['students'] as $studentData) {
                $existing = $existingStudents->get($studentData['student_no']);
                $name = trim((string) ($studentData['name'] ?? ''));
                $email = filled($studentData['email'] ?? null)
                    ? strtolower(trim((string) $studentData['email']))
                    : Student::emailFromStudentNo((string) $studentData['student_no']);

                if ($existing !== null) {
                    // 已有帳號：以帳號姓名／信箱為準（前端可自動帶入；打錯也不擋）
                    $accountName = trim((string) $existing->name);
                    $name = $accountName !== '' ? $accountName : $name;
                    $accountEmail = trim((string) $existing->email);
                    $email = $accountEmail !== '' ? $accountEmail : $email;

                    StudentApplicationItems::create([
                        'application_id' => $application->id,
                        'student_no' => $studentData['student_no'],
                        'name' => $name,
                        'email' => $email,
                        'status' => 'approved',
                    ]);

                    Enrollment::query()->firstOrCreate([
                        'student_id' => $existing->id,
                        'course_id' => $course->id,
                    ]);

                    continue;
                }

                StudentApplicationItems::create([
                    'application_id' => $application->id,
                    'student_no' => $studentData['student_no'],
                    'name' => $name,
                    'email' => $email,
                    'status' => 'pending',
                ]);
            }

            return $application->fresh();
        });
    }

    /**
     * 依學號或姓名查詢是否已有學生帳號（供教師新增時自動帶入）。
     *
     * - student_no：精確比對，最多 1 筆
     * - name：精確比對姓名；可能多人同名，回傳 matches
     *
     * @return array{
     *   has_account: bool,
     *   student_no: string|null,
     *   name: string|null,
     *   matches: list<array{student_no: string, name: string}>
     * }
     */
    public function lookupStudent(?string $studentNo, ?string $name): array
    {
        $normalizedNo = $this->normalizeStudentNo((string) ($studentNo ?? ''));
        $normalizedName = trim((string) ($name ?? ''));

        if ($normalizedNo !== '') {
            $student = Student::query()->where('student_no', $normalizedNo)->first();

            if ($student === null) {
                return [
                    'has_account' => false,
                    'student_no' => $normalizedNo,
                    'name' => null,
                    'matches' => [],
                ];
            }

            return [
                'has_account' => true,
                'student_no' => $student->student_no,
                'name' => $student->name,
                'matches' => [[
                    'student_no' => $student->student_no,
                    'name' => $student->name,
                ]],
            ];
        }

        if ($normalizedName !== '') {
            $students = Student::query()
                ->where('name', $normalizedName)
                ->orderBy('student_no')
                ->get(['student_no', 'name']);

            $matches = $students
                ->map(fn (Student $student) => [
                    'student_no' => $student->student_no,
                    'name' => $student->name,
                ])
                ->values()
                ->all();

            if (count($matches) === 1) {
                return [
                    'has_account' => true,
                    'student_no' => $matches[0]['student_no'],
                    'name' => $matches[0]['name'],
                    'matches' => $matches,
                ];
            }

            return [
                'has_account' => count($matches) > 0,
                'student_no' => null,
                'name' => $normalizedName,
                'matches' => $matches,
            ];
        }

        return [
            'has_account' => false,
            'student_no' => null,
            'name' => null,
            'matches' => [],
        ];
    }

    /**
     * @deprecated 改用 lookupStudent()
     * @return array{has_account: bool, student_no: string, name: string|null}
     */
    public function lookupByStudentNo(string $studentNo): array
    {
        $result = $this->lookupStudent($studentNo, null);

        return [
            'has_account' => $result['has_account'],
            'student_no' => (string) ($result['student_no'] ?? ''),
            'name' => $result['name'],
        ];
    }

    private function normalizeStudentNo(string $value): string
    {
        $studentNo = preg_replace('/\s+/u', '', $value) ?? '';
        if (preg_match('/^[sS](\d+)$/', $studentNo, $matches) === 1) {
            return $matches[1];
        }

        return $studentNo;
    }

    /**
     * 授課教師修改課程名冊中的一位學生（學號、姓名、信箱）。
     *
     * - pending：更新申請列（含信箱）；開通時會沿用此信箱
     * - approved 且已有帳號：可改學號／信箱；姓名以帳號為準，不覆寫正式帳號姓名
     * - 有帶 email：寫入申請列（及帳號）；未帶且改了學號：同步為預設 s{學號}@nutc.edu.tw
     *
     * @param  array{student_no: string, name: string, email?: string|null}  $data
     */
    public function updateItemForCourse(Teacher $teacher, int $courseId, int $itemId, array $data): StudentApplicationItems
    {
        return DB::transaction(function () use ($teacher, $courseId, $itemId, $data) {
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

            $newStudentNo = $data['student_no'];
            $newName = trim($data['name']);
            $emailProvided = array_key_exists('email', $data) && filled($data['email']);
            $newEmail = $emailProvided
                ? strtolower(trim((string) $data['email']))
                : null;

            $duplicateOnCourse = StudentApplicationItems::query()
                ->where('student_no', $newStudentNo)
                ->where('id', '!=', $item->id)
                ->whereHas(
                    'application',
                    fn ($query) => $query->where('course_id', $course->id),
                )
                ->exists();

            if ($duplicateOnCourse) {
                throw ValidationException::withMessages([
                    'student_no' => ['該課已有相同學號'],
                ]);
            }

            $oldStudentNo = (string) $item->student_no;
            $studentNoChanged = $oldStudentNo !== $newStudentNo;

            $resolvedEmail = $newEmail;
            if ($resolvedEmail === null) {
                if ($studentNoChanged) {
                    $resolvedEmail = Student::emailFromStudentNo($newStudentNo);
                } elseif (filled($item->email)) {
                    $resolvedEmail = strtolower(trim((string) $item->email));
                } else {
                    $resolvedEmail = Student::emailFromStudentNo($newStudentNo);
                }
            }

            $student = null;
            if ($item->status === 'approved') {
                $student = Student::query()->where('student_no', $oldStudentNo)->first();
            }

            $emailConflictQuery = Student::query()->where('email', $resolvedEmail);
            if ($student !== null) {
                $emailConflictQuery->where('id', '!=', $student->id);
            }

            if ($emailConflictQuery->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['此信箱已被其他學生帳號使用'],
                ]);
            }

            if ($student !== null) {
                $accountUpdates = [];

                if ($studentNoChanged) {
                    $conflict = Student::query()
                        ->where('student_no', $newStudentNo)
                        ->where('id', '!=', $student->id)
                        ->exists();

                    if ($conflict) {
                        throw ValidationException::withMessages([
                            'student_no' => ['此學號已有其他學生帳號，無法修改'],
                        ]);
                    }

                    $accountUpdates['student_no'] = $newStudentNo;
                }

                if ($resolvedEmail !== (string) $student->email) {
                    $accountUpdates['email'] = $resolvedEmail;
                }

                if ($accountUpdates !== []) {
                    $student->update($accountUpdates);
                }

                // 已有正式帳號：名冊姓名跟帳號走，避免課程端打錯覆寫帳號
                $accountName = trim((string) $student->fresh()->name);
                $newName = $accountName !== '' ? $accountName : $newName;
            }

            $item->update([
                'student_no' => $newStudentNo,
                'name' => $newName,
                'email' => $resolvedEmail,
            ]);

            return $item->fresh(['application.teacher']);
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
     * 管理員：開通一門申請來源課底下全部待審學生，並寫入一門或多門課程選課
     *
     * @param  int  $sourceCourseId 教師申請時綁定的課程（名冊來源）
     * @param  list<int>  $courseIds 欲開通（選課）的課程
     * @return array{activated_count: int, created_count: int, enrolled_count: int, created_by_teacher: array<int, mixed>}
     */
    public function approvePendingForSourceCourse(int $sourceCourseId, array $courseIds): array
    {
        Course::query()->findOrFail($sourceCourseId);

        $itemIds = StudentApplicationItems::query()
            ->where('status', 'pending')
            ->whereHas('application', fn ($query) => $query->where('course_id', $sourceCourseId))
            ->pluck('id')
            ->all();

        if ($itemIds === []) {
            throw ValidationException::withMessages([
                'source_course_id' => ['此課程目前沒有待開通學生。'],
            ]);
        }

        return $this->approveItems($courseIds, $itemIds);
    }

    /**
     * 管理員：批次審核開通勾選的學生，並寫入一門或多門課程選課
     *
     * 處理邏輯：
     * 1. 若學生帳號不存在，自動建立 Student 與初始密碼（累計至通知教師清單）。
     * 2. 對每個選定課程，若尚未選修則建立 Enrollment。
     * 3. 更新申請項目為 approved；主單無待審項目時一併改為 approved。
     *
     * @param  list<int>  $courseIds 欲開通（選課）的課程 ID
     * @param  list<int>  $itemIds 欲開通的申請項目 ID
     * @return array{activated_count: int, created_count: int, enrolled_count: int, created_by_teacher: array<int, array{course_name: string, teacher_account: string, teacher_email: string, teacher_name: string, class_name: string, students: array<int, array{sid: int, class_name: string, student_no: string, name: string, password: string, email: string}>}>}
     * @throws ValidationException 當項目不存在／已開通，或課程無效時拋出
     */
    public function approveItems(array $courseIds, array $itemIds): array
    {
        $uniqueCourseIds = array_values(array_unique(array_map('intval', $courseIds)));
        $uniqueIds = array_values(array_unique(array_map('intval', $itemIds)));

        $courses = Course::query()->whereIn('id', $uniqueCourseIds)->get();
        if ($courses->count() !== count($uniqueCourseIds)) {
            throw ValidationException::withMessages([
                'course_ids' => ['部分課程不存在。'],
            ]);
        }

        return DB::transaction(function () use ($courses, $uniqueIds) {
            $items = StudentApplicationItems::query()
                ->with('application.teacher')
                ->whereIn('id', $uniqueIds)
                ->where('status', 'pending')
                ->get();

            if ($items->count() !== count($uniqueIds)) {
                throw ValidationException::withMessages([
                    'item_ids' => ['部分學生不存在或已開通。'],
                ]);
            }

            $createdByTeacher = [];
            $createdCount = 0;
            $enrolledCount = 0;
            $courseNames = $courses->pluck('name')->unique()->implode('、');

            foreach ($items as $item) {
                $student = Student::query()
                    ->where('student_no', $item->student_no)
                    ->first();

                $plainPassword = null;
                // 申請列有信箱（新增／修改時填過）→ 開通建帳用該信箱
                // 申請列沒有 → 用學號組成預設 s{學號}@nutc.edu.tw
                $email = filled($item->email)
                    ? strtolower(trim((string) $item->email))
                    : Student::emailFromStudentNo($item->student_no);

                if ($student === null) {
                    $plainPassword = $this->generatePassword();

                    $student = Student::create([
                        'class_name' => $item->application?->class_name,
                        'student_no' => $item->student_no,
                        'name' => $item->name !== '' ? $item->name : $item->student_no,
                        'password' => $plainPassword,
                        'email' => $email,
                    ]);

                    $createdCount++;
                }

                foreach ($courses as $course) {
                    $alreadyEnrolled = Enrollment::query()
                        ->where('student_id', $student->id)
                        ->where('course_id', $course->id)
                        ->exists();

                    if (! $alreadyEnrolled) {
                        Enrollment::query()->create([
                            'student_id' => $student->id,
                            'course_id' => $course->id,
                        ]);
                        $enrolledCount++;
                    }
                }

                $item->update(['status' => 'approved']);

                $application = $item->application;
                if ($application !== null && ! $application->items()->where('status', 'pending')->exists()) {
                    $application->update(['status' => 'approved']);
                }

                if ($plainPassword !== null && $application?->teacher !== null) {
                    $teacherId = $application->tid;
                    $createdByTeacher[$teacherId] ??= [
                        'course_name' => $courseNames,
                        'teacher_account' => $application->teacher->account,
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
                'enrolled_count' => $enrolledCount,
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

        return $this->approveItems([(int) $application->course_id], $itemIds);
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
