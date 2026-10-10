<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\ReviewQuestionRecordRequest;
use App\Models\Teacher;
use App\Services\TeacherQuestionRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionRecordController extends Controller
{
    public function __construct(
        private readonly TeacherQuestionRecordService $teacherQuestionRecordService,
    ) {}

    public function index(Request $request, int $courseId): JsonResponse
    {
        return response()->json([
            'records' => $this->teacherQuestionRecordService->listForTeacher(
                $this->teacher($request),
                $courseId,
            ),
        ]);
    }

    public function update(ReviewQuestionRecordRequest $request, int $recordId): JsonResponse
    {
        return response()->json([
            'record' => $this->teacherQuestionRecordService->review(
                $this->teacher($request),
                $recordId,
                $request->validated(),
            ),
        ]);
    }

    public function grade(Request $request, int $recordId, \App\Services\CodingGradingService $codingGradingService): JsonResponse
    {
        $teacher = $this->teacher($request);
        $record = \App\Models\QuestionRecord::query()
            ->whereKey($recordId)
            ->whereHas('question.course', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->firstOrFail();

        // 確保老師不會在實作題以外的題型誤點及
        if ($record->question?->type !== \App\Models\Question::TYPE_CODING) {
            return response()->json([
                'message' => '只有程式實作題支援 AI 批改。',
            ], 422);
        }

        $result = $codingGradingService->grade($record);

        $record->load(['student', 'question', 'subs', 'aiFeedback']);

        // 老師批改完成後直接更新資料表
        return response()->json([
            'message' => 'AI 批改完成',
            'record'  => $this->teacherQuestionRecordService->formatRecord($record),
            'data'    => $result,
        ]);
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
