<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\ImportMaterialRequest;
use App\Models\Teacher;
use App\Services\MaterialImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialImportController extends Controller
{
    public function __construct(
        private readonly MaterialImportService $materialImportService,
    ) {}

    /**
     * 接收教師上傳的 Excel，直接寫入正式教材。前端不要自己解析檔案。
     */
    public function store(ImportMaterialRequest $request, int $courseId): JsonResponse
    {
        $path = $request->file('file')->getRealPath();

        return response()->json([
            'topic' => $this->materialImportService->import(
                $this->teacher($request),
                $courseId,
                $path,
                $request->string('topic')->toString(),
                $request->boolean('overwrite'),
            ),
        ], 201);
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
