<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\MaterialNameRequest;
use App\Models\Teacher;
use App\Services\MaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    public function __construct(
        private readonly MaterialService $materialService,
    ) {}

    public function index(Request $request, int $topicId): JsonResponse
    {
        return response()->json([
            'chapters' => $this->materialService->listChapters($this->teacher($request), $topicId),
        ]);
    }

    public function store(MaterialNameRequest $request, int $topicId): JsonResponse
    {
        return response()->json([
            'chapter' => $this->materialService->createChapter($this->teacher($request), $topicId, $request->validated()),
        ], 201);
    }

    public function update(MaterialNameRequest $request, int $chapterId): JsonResponse
    {
        return response()->json([
            'chapter' => $this->materialService->updateChapter($this->teacher($request), $chapterId, $request->validated()),
        ]);
    }

    public function destroy(Request $request, int $chapterId): JsonResponse
    {
        $this->materialService->deleteChapter($this->teacher($request), $chapterId);

        return response()->json([
            'message' => '章節已刪除',
        ]);
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();

        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
