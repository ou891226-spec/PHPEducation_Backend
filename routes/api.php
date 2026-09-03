<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Controllers\Api\V1\Student\MaterialController as StudentMaterialController;
use App\Http\Controllers\Api\V1\Student\QuestionController as StudentQuestionController;
use App\Http\Controllers\Api\V1\Teacher\ChapterController;
use App\Http\Controllers\Api\V1\Teacher\CourseController;
use App\Http\Controllers\Api\V1\Teacher\EditorImageController;
use App\Http\Controllers\Api\V1\Teacher\KnowledgeCardController;
use App\Http\Controllers\Api\V1\Teacher\MaterialGraphController;
use App\Http\Controllers\Api\V1\Teacher\MaterialImportController;
use App\Http\Controllers\Api\V1\Teacher\MaterialTemplateController;
use App\Http\Controllers\Api\V1\Teacher\StudentRosterTemplateController;
use App\Http\Controllers\Api\V1\Teacher\QuestionController as TeacherQuestionController;
use App\Http\Controllers\Api\V1\Teacher\QuestionRecordController;
use App\Http\Controllers\Api\V1\Teacher\UnitController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TeacherApplicationController;
use App\Http\Controllers\TeacherApprovementController;
use App\Http\Controllers\StudentAccountApplicationController;
use App\Http\Controllers\StudentApprovementController;

Route::prefix('v1')->group(function () {

    // 教師 / 學生批次帳號 申請路由
    Route::post('/teacher-applications', [TeacherApplicationController::class, 'store']);
    Route::post('/teacher/student-applications', [StudentAccountApplicationController::class, 'store']);

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/dashboard', [DashboardController::class, 'show']);

        Route::middleware('role:admin')->group(function () {
            Route::get('/stats', [StatsController::class, 'show']);
            Route::get('/courses', [StudentAccountApplicationController::class, 'courses']);
            Route::get('/teacher-applications', [TeacherApplicationController::class, 'index']);
            Route::get('/student-applications', [StudentAccountApplicationController::class, 'index']);
            Route::post('/student-applications/approve', [StudentApprovementController::class, 'approveSelected']);
            Route::post('/teacher-applications/{id}/approve', [TeacherApprovementController::class, 'approve']);
            Route::post('/teacher/student-applications/{id}/approve', [StudentApprovementController::class, 'approve']);
        });

        Route::middleware('role:teacher')->prefix('teacher')->group(function () {
            Route::apiResource('courses', CourseController::class)->parameters([
                'courses' => 'courseId',
            ]);

            Route::get('materials/template', [MaterialTemplateController::class, 'download']);
            Route::post('upload-image', [EditorImageController::class, 'store']);
            Route::get('student-applications/template', [StudentRosterTemplateController::class, 'download']);
            Route::post('courses/{courseId}/materials/import', [MaterialImportController::class, 'store']);
            Route::get('courses/{courseId}/tree', [MaterialGraphController::class, 'courseTree']);
            Route::get('courses/{courseId}/student-applications', [StudentAccountApplicationController::class, 'indexForCourse']);
            Route::post('courses/{courseId}/student-applications', [StudentAccountApplicationController::class, 'storeOneForCourse']);
            Route::delete('courses/{courseId}/student-applications/{itemId}', [StudentAccountApplicationController::class, 'destroyForCourse']);

            Route::get('courses/{courseId}/knowledge-cards', [KnowledgeCardController::class, 'indexForCourse']);
            Route::get('courses/{courseId}/chapters', [ChapterController::class, 'index']);
            Route::post('courses/{courseId}/chapters', [ChapterController::class, 'store']);
            Route::put('chapters/{chapterId}', [ChapterController::class, 'update']);
            Route::delete('chapters/{chapterId}', [ChapterController::class, 'destroy']);

            Route::get('chapters/{chapterId}/units', [UnitController::class, 'index']);
            Route::post('chapters/{chapterId}/units', [UnitController::class, 'store']);
            Route::put('units/{unitId}', [UnitController::class, 'update']);
            Route::delete('units/{unitId}', [UnitController::class, 'destroy']);

            Route::get('units/{unitId}/knowledge-cards', [KnowledgeCardController::class, 'index']);
            Route::post('units/{unitId}/knowledge-cards', [KnowledgeCardController::class, 'store']);
            Route::put('knowledge-cards/{cardId}', [KnowledgeCardController::class, 'update']);
            Route::delete('knowledge-cards/{cardId}', [KnowledgeCardController::class, 'destroy']);

            Route::get('blooms', [TeacherQuestionController::class, 'blooms']);
            Route::get('courses/{courseId}/questions', [TeacherQuestionController::class, 'index']);
            Route::post('courses/{courseId}/questions', [TeacherQuestionController::class, 'store']);
            Route::get('questions/{questionId}', [TeacherQuestionController::class, 'show']);
            Route::put('questions/{questionId}', [TeacherQuestionController::class, 'update']);
            Route::delete('questions/{questionId}', [TeacherQuestionController::class, 'destroy']);

            Route::get('courses/{courseId}/question-records', [QuestionRecordController::class, 'index']);
            Route::put('question-records/{recordId}', [QuestionRecordController::class, 'update']);
        });

        Route::middleware('role:student')->prefix('student')->group(function () {
            Route::get('courses/{courseId}/graph', [StudentMaterialController::class, 'graph']);
            Route::get('courses/{courseId}/chapters', [StudentMaterialController::class, 'chapters']);
            Route::get('chapters/{chapterId}/units', [StudentMaterialController::class, 'units']);
            Route::get('units/{unitId}/knowledge-cards', [StudentMaterialController::class, 'knowledgeCards']);
            Route::get('courses/{courseId}/questions', [StudentQuestionController::class, 'index']);
            Route::get('questions/{questionId}', [StudentQuestionController::class, 'show']);
            Route::post('questions/{questionId}/submit', [StudentQuestionController::class, 'submit']);
        });
    });
});
