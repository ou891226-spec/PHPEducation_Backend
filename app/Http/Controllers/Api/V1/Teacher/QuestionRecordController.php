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

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
