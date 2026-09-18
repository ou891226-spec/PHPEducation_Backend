<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function __construct(
        private readonly StatsService $statsService,
    ) {}

    /**
     * 管理員：老師、學生、課程數量。
     */
    public function show(): JsonResponse
    {
        return response()->json($this->statsService->counts());
    }
}
