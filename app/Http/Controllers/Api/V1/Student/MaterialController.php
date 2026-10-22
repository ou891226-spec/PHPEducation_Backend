<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function __construct(
        private readonly StudentMaterialService $studentMaterialService,
    ) {}

    public function chapters(Request $request, int $courseId): JsonResponse
    {
        return response()->json([
            'chapters' => $this->studentMaterialService->listChapters($this->student($request), $courseId),
        ]);
    }

    public function units(Request $request, int $chapterId): JsonResponse
    {
        return response()->json([
            'units' => $this->studentMaterialService->listUnits($this->student($request), $chapterId),
        ]);
    }

    public function knowledgeCards(Request $request, int $unitId): JsonResponse
    {
        return response()->json([
            'knowledge_cards' => $this->studentMaterialService->listKnowledgeCards($this->student($request), $unitId),
        ]);
    }

    public function graph(Request $request, int $courseId): JsonResponse
    {
        return response()->json([
            'graph' => $this->studentMaterialService->courseGraph($this->student($request), $courseId),
        ]);
    }

    /**
     * 學生端取得單一單元圖譜
     */
    public function unitGraph(Request $request, int $unitId): JsonResponse
    {
        return response()->json([
            'unit' => $this->studentMaterialService->unitGraph($this->student($request), $unitId),
        ]);
    }

    private function student(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user instanceof Student, 403, 'Forbidden');

        return $user;
    }
}
