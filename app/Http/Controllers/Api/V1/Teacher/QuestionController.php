<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\StoreTeacherQuestionRequest;
use App\Models\Bloom;
use App\Models\Teacher;
use App\Services\TeacherQuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function __construct(
        private readonly TeacherQuestionService $teacherQuestionService,
    ) {}

    public function blooms(): JsonResponse
    {
        return response()->json([
            'blooms' => Bloom::query()
                ->whereIn('id', Bloom::teacherChoiceIds())
                ->orderBy('id')
                ->get(['id', 'title', 'cognition_info'])
                ->map(fn (Bloom $bloom) => [
                    'id' => $bloom->id,
                    'title' => Bloom::teacherChoiceTitle($bloom->id),
                    'cognition_info' => $bloom->cognition_info,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function index(Request $request, int $courseId): JsonResponse
    {
        return response()->json([
            'questions' => $this->teacherQuestionService->listForCourse($this->teacher($request), $courseId),
        ]);
    }

    public function store(StoreTeacherQuestionRequest $request, int $courseId): JsonResponse
    {
        return response()->json([
            'question' => $this->teacherQuestionService->create(
                $this->teacher($request),
                $courseId,
                $request->validated(),
            ),
        ], 201);
    }

    public function show(Request $request, int $questionId): JsonResponse
    {
        return response()->json([
            'question' => $this->teacherQuestionService->findForTeacher($this->teacher($request), $questionId),
        ]);
    }

    public function update(StoreTeacherQuestionRequest $request, int $questionId): JsonResponse
    {
        return response()->json([
            'question' => $this->teacherQuestionService->update(
                $this->teacher($request),
                $questionId,
                $request->validated(),
            ),
        ]);
    }

    public function destroy(Request $request, int $questionId): JsonResponse
    {
        $this->teacherQuestionService->delete($this->teacher($request), $questionId);

        return response()->json([
            'message' => '題目已刪除',
        ]);
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
