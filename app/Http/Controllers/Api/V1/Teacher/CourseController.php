<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Models\Teacher;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courseService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $teacher = $this->requireTeacher($request);

        return response()->json([
            'courses' => $this->courseService->listForTeacher($teacher),
        ]);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $teacher = $this->requireTeacher($request);

        $course = $this->courseService->create($teacher, $request->validated());

        return response()->json([
            'course' => $course,
        ], 201);
    }

    public function show(Request $request, int $courseId): JsonResponse
    {
        $teacher = $this->requireTeacher($request);

        return response()->json([
            'course' => $this->courseService->findForTeacher($teacher, $courseId),
        ]);
    }

    public function update(UpdateCourseRequest $request, int $courseId): JsonResponse
    {
        $teacher = $this->requireTeacher($request);

        return response()->json([
            'course' => $this->courseService->update($teacher, $courseId, $request->validated()),
        ]);
    }

    public function destroy(Request $request, int $courseId): JsonResponse
    {
        $teacher = $this->requireTeacher($request);

        $this->courseService->delete($teacher, $courseId);

        return response()->json([
            'message' => '課程已刪除',
        ]);
    }

    private function requireTeacher(Request $request): Teacher
    {
        $user = $request->user();

        abort_unless($user instanceof Teacher, 403, 'Forbidden');

        return $user;
    }
}
