<?php
namespace App\Services;
use App\Models\StudentApplications;
use App\Models\StudentApplicationItems;
use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Class StudentAccountService
 * 專責處理學生帳號申請主單建立與批次開通建立的服務
 */
class StudentAccountService
{
    public function __construct(
        private readonly ExcelStudentRosterParser $rosterParser,
    ) {}

    /**
     * 解析 Excel 名冊並建立學生帳號申請單（含明細）。
     * 班級取自課程，不從 Excel 讀。
     *
     * @param  array{course_id: int}  $data
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
     * @param  array{course_id: int, students: list<array{student_no: string, name: string}>}  $data
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

            $studentNos = array_column($data['students'], 'student_no');
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
     * 開通勾選的學生：沒帳號就建＋選課，有帳號只選課。
     *
     * @param  array<int, int>  $itemIds
     * @return array{activated_count: int, created_count: int, enrolled_count: int, created_by_teacher: array<int, array{teacher_email: string, teacher_name: string, class_name: string, students: array<int, array<string, mixed>>}>}
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

                $application = $item->application;
                if ($application !== null && ! $application->items()->where('status', 'pending')->exists()) {
                    $application->update(['status' => 'approved']);
                }

                if ($plainPassword !== null && $application?->teacher !== null) {
                    $teacherId = $application->tid;
                    $createdByTeacher[$teacherId] ??= [
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
     * 整張申請單尚未開通的人一次開通。
     *
     * @return array{activated_count: int, created_count: int, enrolled_count: int, created_by_teacher: array<int, array{teacher_email: string, teacher_name: string, class_name: string, students: array<int, array<string, mixed>>}>}
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

    private function generatePassword(): string
    {
        return Str::random(12);
    }
}
