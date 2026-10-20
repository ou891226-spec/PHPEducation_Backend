<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\SubmitQuestionRequest;
use App\Models\Student;
use App\Services\StudentKnowledgeCardService;
use App\Services\StudentLearningAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeCardQuizController extends Controller
{
    public function __construct(
        private readonly StudentKnowledgeCardService $knowledgeCardService,
        private readonly StudentLearningAnalyticsService $analyticsService,
    ) {}

    public function analytics(Request $request, int $courseId): JsonResponse
    {
        return response()->json([
            'analytics' => $this->analyticsService->analytics($this->student($request), $courseId),
        ]);
    }

    public function show(Request $request, int $courseId, int $cardId): JsonResponse
    {
        return response()->json(
            $this->knowledgeCardService->quiz($this->student($request), $courseId, $cardId),
        );
    }

    public function submit(SubmitQuestionRequest $request, int $courseId, int $cardId, int $questionId): JsonResponse
    {
        return response()->json(
            $this->knowledgeCardService->submitQuiz(
                $this->student($request),
                $courseId,
                $cardId,
                $questionId,
                $request->validated(),
            ),
        );
    }

    public function read(Request $request, int $courseId, int $cardId): JsonResponse
    {
        return response()->json(
            $this->knowledgeCardService->markRead($this->student($request), $courseId, $cardId),
        );
    }

    public function progress(Request $request, int $courseId): JsonResponse
    {
        return response()->json([
            'progress' => $this->knowledgeCardService->progress($this->student($request), $courseId),
        ]);
    }

    private function student(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user instanceof Student, 403, 'Forbidden');

        return $user;
    }
}
