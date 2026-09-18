<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 主題表
     */
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id()->comment('主題 ID');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete()->comment('所屬課程 ID');
            $table->string('name')->comment('主題名稱');
            $table->integer('sort_order')->comment('排序順序');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
