<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\SubmitQuestionRequest;
use App\Models\Student;
use App\Services\StudentQuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function __construct(
        private readonly StudentQuestionService $studentQuestionService,
    ) {}

    public function index(Request $request, int $courseId): JsonResponse
    {
        $knowledgeCardId = $request->query('knowledge_card_id');

        return response()->json([
            'questions' => $this->studentQuestionService->listForStudent(
                $this->student($request),
                $courseId,
                $knowledgeCardId !== null ? (int) $knowledgeCardId : null,
            ),
        ]);
    }

    public function show(Request $request, int $questionId): JsonResponse
    {
        return response()->json([
            'question' => $this->studentQuestionService->findForStudent($this->student($request), $questionId),
        ]);
    }

    public function submit(SubmitQuestionRequest $request, int $questionId): JsonResponse
    {
        return response()->json(
            $this->studentQuestionService->submit(
                $this->student($request),
                $questionId,
                $request->validated(),
            )
        );
    }

    private function student(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user instanceof Student, 403, 'Forbidden');

        return $user;
    }
}
