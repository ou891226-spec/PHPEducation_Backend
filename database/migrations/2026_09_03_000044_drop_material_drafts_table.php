<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 修改教材改為Excel直接寫正式表，不再使用草稿。移除草稿表。
     */
    public function up(): void
    {
        Schema::dropIfExists('material_drafts');
    }

    public function down(): void
    {
        Schema::create('material_drafts', function (Blueprint $table) {
            $table->id()->comment('草稿 ID');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete()->comment('所屬課程 ID');
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete()->comment('匯入教師 ID');
            $table->string('name')->comment('教材名稱');
            $table->string('status')->default('draft')->comment('draft、published 或 archived');
            $table->json('tree')->comment('主題／章節／單元／知識卡樹');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }
};
