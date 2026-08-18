<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\MaterialNameRequest;
use App\Models\Teacher;
use App\Services\MaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(
        private readonly MaterialService $materialService,
    ) {}

    public function index(Request $request, int $chapterId): JsonResponse
    {
        return response()->json([
            'units' => $this->materialService->listUnits($this->teacher($request), $chapterId),
        ]);
    }

    public function store(MaterialNameRequest $request, int $chapterId): JsonResponse
    {
        return response()->json([
            'unit' => $this->materialService->createUnit($this->teacher($request), $chapterId, $request->validated()),
        ], 201);
    }

    public function update(MaterialNameRequest $request, int $unitId): JsonResponse
    {
        return response()->json([
            'unit' => $this->materialService->updateUnit($this->teacher($request), $unitId, $request->validated()),
        ]);
    }

    public function destroy(Request $request, int $unitId): JsonResponse
    {
        $this->materialService->deleteUnit($this->teacher($request), $unitId);

        return response()->json([
            'message' => '單元已刪除',
        ]);
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();

        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
