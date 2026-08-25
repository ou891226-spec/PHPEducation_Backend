<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\KnowledgeCardRequest;
use App\Http\Requests\Material\MaterialNameRequest;
use App\Models\Teacher;
use App\Services\MaterialDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialDraftController extends Controller
{
    public function __construct(
        private readonly MaterialDraftService $materialDraftService,
    ) {}

    public function indexForCourse(Request $request, int $courseId): JsonResponse
    {
        return response()->json([
            'drafts' => $this->materialDraftService->listForCourse($this->teacher($request), $courseId),
        ]);
    }

    public function storeFromPublished(Request $request, int $courseId): JsonResponse
    {
        $topicId = $request->input('topic_id');

        return response()->json([
            'draft' => $this->materialDraftService->createFromPublished(
                $this->teacher($request),
                $courseId,
                $topicId !== null && $topicId !== '' ? (int) $topicId : null,
            ),
        ], 201);
    }

    public function storeTopic(MaterialNameRequest $request, int $draftId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->addTopic($this->teacher($request), $draftId, $request->validated()),
        ], 201);
    }

    public function updateTopic(MaterialNameRequest $request, int $draftId, string $nodeId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->updateTopic($this->teacher($request), $draftId, $nodeId, $request->validated()),
        ]);
    }

    public function destroyTopic(Request $request, int $draftId, string $nodeId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->deleteTopic($this->teacher($request), $draftId, $nodeId),
        ]);
    }

    public function storeChapter(MaterialNameRequest $request, int $draftId, string $topicId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->addChapter($this->teacher($request), $draftId, $topicId, $request->validated()),
        ], 201);
    }

    public function updateChapter(MaterialNameRequest $request, int $draftId, string $nodeId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->updateChapter($this->teacher($request), $draftId, $nodeId, $request->validated()),
        ]);
    }

    public function destroyChapter(Request $request, int $draftId, string $nodeId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->deleteChapter($this->teacher($request), $draftId, $nodeId),
        ]);
    }

    public function storeUnit(MaterialNameRequest $request, int $draftId, string $chapterId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->addUnit($this->teacher($request), $draftId, $chapterId, $request->validated()),
        ], 201);
    }

    public function updateUnit(MaterialNameRequest $request, int $draftId, string $nodeId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->updateUnit($this->teacher($request), $draftId, $nodeId, $request->validated()),
        ]);
    }

    public function destroyUnit(Request $request, int $draftId, string $nodeId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->deleteUnit($this->teacher($request), $draftId, $nodeId),
        ]);
    }

    public function storeCard(KnowledgeCardRequest $request, int $draftId, string $unitId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->addCard($this->teacher($request), $draftId, $unitId, $request->validated()),
        ], 201);
    }

    public function updateCard(KnowledgeCardRequest $request, int $draftId, string $nodeId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->updateCard($this->teacher($request), $draftId, $nodeId, $request->validated()),
        ]);
    }

    public function destroyCard(Request $request, int $draftId, string $nodeId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->deleteCard($this->teacher($request), $draftId, $nodeId),
        ]);
    }

    public function publish(Request $request, int $draftId): JsonResponse
    {
        return response()->json([
            'draft' => $this->materialDraftService->publish($this->teacher($request), $draftId),
            'message' => '已發布為正式教材',
        ]);
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
