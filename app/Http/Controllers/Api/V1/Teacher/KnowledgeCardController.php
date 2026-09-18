<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\KnowledgeCardRequest;
use App\Models\Teacher;
use App\Services\MaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeCardController extends Controller
{
    public function __construct(
        private readonly MaterialService $materialService,
    ) {}

    public function index(Request $request, int $unitId): JsonResponse
    {
        return response()->json([
            'knowledge_cards' => $this->materialService->listKnowledgeCards($this->teacher($request), $unitId),
        ]);
    }

    public function indexForCourse(Request $request, int $courseId): JsonResponse
    {
        return response()->json([
            'knowledge_cards' => $this->materialService->listKnowledgeCardsForCourse(
                $this->teacher($request),
                $courseId,
            ),
        ]);
    }

    public function store(KnowledgeCardRequest $request, int $unitId): JsonResponse
    {
        return response()->json([
            'knowledge_card' => $this->materialService->createKnowledgeCard($this->teacher($request), $unitId, $request->validated()),
        ], 201);
    }

    public function update(KnowledgeCardRequest $request, int $cardId): JsonResponse
    {
        return response()->json([
            'knowledge_card' => $this->materialService->updateKnowledgeCard($this->teacher($request), $cardId, $request->validated()),
        ]);
    }

    public function destroy(Request $request, int $cardId): JsonResponse
    {
        $this->materialService->deleteKnowledgeCard($this->teacher($request), $cardId);

        return response()->json([
            'message' => '知識卡已刪除',
        ]);
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();

        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
