<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 學生這次答題的紀錄
     * 有作答歷程時不隨題目刪除
     */
    public function up(): void
    {
        Schema::create('question_records', function (Blueprint $table) {
            $table->id()->comment('學生闖關紀錄編號');
            $table->foreignId('student_id')->constrained('students')->comment('學生編號');
            $table->foreignId('question_id')->constrained('questions')->comment('題目編號');
            $table->text('result')->comment('學生當時的答案');
            $table->foreignId('question_mapping_id')->constrained('question_bloom_solo_mappings')->comment('這次使用的 Bloom + SOLO 組合');
            $table->string('system_status')->default('pending')->comment('pending、correct 或 wrong');
            $table->string('teacher_status')->default('pending')->comment('pending、correct 或 wrong');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_records');
    }
};
