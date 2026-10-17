<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\SubmitQuestionRequest;
use App\Models\Question;
use App\Models\Student;
use App\Services\StudentPracticeService;
use App\Services\StudentQuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function __construct(
        private readonly StudentQuestionService $studentQuestionService,
        private readonly StudentPracticeService $studentPracticeService,
    ) {}

    public function index(Request $request, int $courseId): JsonResponse
    {
        $validated = $request->validate([
            'knowledge_card_id' => ['nullable', 'integer'],
            'chapter_id' => ['nullable', 'integer'],
            'unit_id' => ['nullable', 'integer'],
            'scope' => ['nullable', Rule::in([
                StudentPracticeService::SCOPE_CARD,
                StudentPracticeService::SCOPE_UNIT,
                StudentPracticeService::SCOPE_CHAPTER,
                StudentPracticeService::SCOPE_COURSE,
            ])],
            'type' => ['nullable', Rule::in(Question::TYPES)],
            'status' => ['nullable', Rule::in(['all', 'answered', 'unanswered'])],
            'keyword' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $filters = $validated;
        foreach (['knowledge_card_id', 'chapter_id', 'unit_id', 'page', 'per_page'] as $key) {
            $filters[$key] = isset($validated[$key]) ? (int) $validated[$key] : null;
        }

        return response()->json(
            $this->studentPracticeService->list($this->student($request), $courseId, $filters),
        );
    }

    public function show(Request $request, int $questionId): JsonResponse
    {
        return response()->json([
            'question' => $this->studentPracticeService->find($this->student($request), $questionId),
        ]);
    }

    public function submit(SubmitQuestionRequest $request, int $questionId): JsonResponse
    {
        return response()->json(
            $this->studentPracticeService->submit(
                $this->student($request),
                $questionId,
                $request->validated(),
            )
        );
    }

    public function records(Request $request, int $courseId): JsonResponse
    {
        $questionId = $request->query('question_id');

        return response()->json([
            'records' => $this->studentQuestionService->listRecordsForStudent(
                $this->student($request),
                $courseId,
                $questionId !== null && $questionId !== '' ? (int) $questionId : null,
            ),
        ]);
    }

    public function showRecord(Request $request, int $recordId): JsonResponse
    {
        return response()->json([
            'record' => $this->studentQuestionService->findRecordForStudent(
                $this->student($request),
                $recordId,
            ),
        ]);
    }

    private function student(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user instanceof Student, 403, 'Forbidden');

        return $user;
    }
}
