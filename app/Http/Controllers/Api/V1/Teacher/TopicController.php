<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\MaterialNameRequest;
use App\Models\Teacher;
use App\Services\MaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function __construct(
        private readonly MaterialService $materialService,
    ) {}

    public function index(Request $request, int $courseId): JsonResponse
    {
        return response()->json([
            'topics' => $this->materialService->listTopics($this->teacher($request), $courseId),
        ]);
    }

    public function store(MaterialNameRequest $request, int $courseId): JsonResponse
    {
        return response()->json([
            'topic' => $this->materialService->createTopic($this->teacher($request), $courseId, $request->validated()),
        ], 201);
    }

    public function update(MaterialNameRequest $request, int $topicId): JsonResponse
    {
        return response()->json([
            'topic' => $this->materialService->updateTopic($this->teacher($request), $topicId, $request->validated()),
        ]);
    }

    public function destroy(Request $request, int $topicId): JsonResponse
    {
        $this->materialService->deleteTopic($this->teacher($request), $topicId);

        return response()->json([
            'message' => '主題已刪除',
        ]);
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();

        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
