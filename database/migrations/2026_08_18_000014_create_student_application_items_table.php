<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 學生申請物件表
     * 一筆學生申請可包含多位學生
     */
    public function up(): void
    {
        Schema::create('student_application_items', function (Blueprint $table) {
            $table->id()->comment('明細 ID');

            $table->foreignId('application_id')->constrained('student_applications')->cascadeOnDelete()->comment('申請 ID（教師編號）');
            $table->string('student_no')->comment('學生學號');
            $table->string('name')->comment('學生姓名');
            $table->string('email')->comment('信箱');
            $table->unique(['application_id', 'student_no']);

            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_application_items');
    }
};
