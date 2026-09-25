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
     * 接收教師上傳的 Excel，依匯入方式寫入正式教材。前端不要自己解析檔案。
     */
    public function store(ImportMaterialRequest $request, int $courseId): JsonResponse
    {
        return response()->json(
            $this->materialImportService->import(
                $this->teacher($request),
                $courseId,
                $request->file('file')->getRealPath(),
                $request->importMode(),
                $request->chapterId(),
                $request->fingerprint(),
            ),
            201,
        );
    }

    /**
     * 匯入預覽：回傳匯入後的教材樹與影響範圍，不寫入資料庫。
     */
    public function preview(ImportMaterialRequest $request, int $courseId): JsonResponse
    {
        return response()->json(
            $this->materialImportService->preview(
                $this->teacher($request),
                $courseId,
                $request->file('file')->getRealPath(),
                $request->importMode(),
                $request->chapterId(),
            ),
        );
    }

    private function teacher(Request $request): Teacher
    {
        $user = $request->user();
        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
