<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\ImportMaterialRequest;
use App\Models\Teacher;
use App\Services\MaterialDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialImportController extends Controller
{
    public function __construct(
        private readonly MaterialDraftService $materialDraftService,
    ) {}

    /**
     * 接收教師上傳的 Excel，由後端解析後存成 Draft。前端不要自己解析檔案。
     */
    public function store(ImportMaterialRequest $request, int $courseId): JsonResponse
    {
        $path = $request->file('file')->getRealPath();

        return response()->json([
            'draft' => $this->materialDraftService->import($this->teacher($request), $courseId, $path),
        ], 201);
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
