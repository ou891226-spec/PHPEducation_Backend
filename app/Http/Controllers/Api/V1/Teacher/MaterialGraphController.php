<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\MaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialGraphController extends Controller
{
    public function __construct(
        private readonly MaterialService $materialService,
    ) {}

    public function courseTree(Request $request, int $courseId): JsonResponse
    {
        return response()->json([
            'course' => $this->materialService->courseTree($this->teacher($request), $courseId),
        ]);
    }

    /**
     * 教師端取得單一單元圖譜
     */
    public function unitGraph(Request $request, int $unitId): JsonResponse
    {
        return response()->json([
            'unit' => $this->materialService->unitGraph($this->teacher($request), $unitId),
        ]);
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
