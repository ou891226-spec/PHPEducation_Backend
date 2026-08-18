<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 學生選課表
     */
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete()->comment('學生 ID');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete()->comment('課程 ID');

            $table->primary(['student_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
