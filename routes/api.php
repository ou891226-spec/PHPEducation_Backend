<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\Teacher\ChapterController;
use App\Http\Controllers\Api\V1\Teacher\CourseController;
use App\Http\Controllers\Api\V1\Teacher\KnowledgeCardController;
use App\Http\Controllers\Api\V1\Teacher\TopicController;
use App\Http\Controllers\Api\V1\Teacher\UnitController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TeacherApplicationController;
use App\Http\Controllers\TeacherApprovementController;
use App\Http\Controllers\StudentAccountApplicationController;
use App\Http\Controllers\StudentApprovementController;

Route::prefix('v1')->group(function () {

    // 教師帳號 申請/審核 相關路由
    Route::post('/teacher-applications', [TeacherApplicationController::class, 'store']);
    Route::post('/teacher-applications/{id}/approve', [TeacherApprovementController::class, 'approve']);

    // 學生批次帳號 申請/審核 相關路由
    Route::post('/teacher/student-applications', [StudentAccountApplicationController::class, 'store']);
    Route::post('/teacher/student-applications/{id}/approve', [StudentApprovementController::class, 'approve']);

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/dashboard', [DashboardController::class, 'show']);

        Route::middleware('role:teacher')->prefix('teacher')->group(function () {
            Route::apiResource('courses', CourseController::class)->parameters([
                'courses' => 'courseId',
            ]);

            Route::get('courses/{courseId}/topics', [TopicController::class, 'index']);
            Route::post('courses/{courseId}/topics', [TopicController::class, 'store']);
            Route::put('topics/{topicId}', [TopicController::class, 'update']);
            Route::delete('topics/{topicId}', [TopicController::class, 'destroy']);

            Route::get('topics/{topicId}/chapters', [ChapterController::class, 'index']);
            Route::post('topics/{topicId}/chapters', [ChapterController::class, 'store']);
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
        });
    });
});
