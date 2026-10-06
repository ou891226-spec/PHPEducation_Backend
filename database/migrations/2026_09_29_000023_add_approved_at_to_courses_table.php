<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 課程第一次由管理員開通的時間
     * 尚未開通（null）的課程，連已有帳號的學生也要等管理員開通才加入
     */
    public function up(): void
    {
        if (Schema::hasColumn('courses', 'approved_at')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->timestamp('approved_at')
                ->nullable()
                ->after('class_name')
                ->comment('管理員第一次開通課程的時間');
        });

        // 既有課程：已有選課或已開通的申請學生，視為開通過
        DB::table('courses')
            ->whereNull('approved_at')
            ->where(function ($query) {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('enrollments')
                        ->whereColumn('enrollments.course_id', 'courses.id');
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('student_applications')
                        ->join('student_application_items', 'student_application_items.application_id', '=', 'student_applications.id')
                        ->whereColumn('student_applications.course_id', 'courses.id')
                        ->where('student_application_items.status', 'approved');
                });
            })
            ->update(['approved_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
