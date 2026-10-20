<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\TeacherStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(
        private readonly TeacherStatsService $teacherStatsService,
    ) {}

    public function bloom(Request $request, int $courseId): JsonResponse
    {
        return response()->json(
            $this->teacherStatsService->bloom($this->teacher($request), $courseId),
        );
    }

    public function solo(Request $request, int $courseId): JsonResponse
    {
        return response()->json(
            $this->teacherStatsService->questionSolo($this->teacher($request), $courseId),
        );
    }

    public function studentSolo(Request $request, int $courseId): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['nullable', 'integer'],
        ]);

        return response()->json(
            $this->teacherStatsService->studentSolo(
                $this->teacher($request),
                $courseId,
                isset($validated['student_id']) ? (int) $validated['student_id'] : null,
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
