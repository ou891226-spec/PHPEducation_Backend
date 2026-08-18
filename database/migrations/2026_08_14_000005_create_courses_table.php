<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 課程表
     * 課程由教師建立
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id()->comment('課程 ID');
            $table->string('name')->comment('課程名稱');
            $table->text('description')->nullable()->comment('課程介紹');
            $table->string('semester')->comment('開課學期');
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete()->comment('授課教師 ID');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
