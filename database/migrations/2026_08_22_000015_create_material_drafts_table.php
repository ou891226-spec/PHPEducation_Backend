<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 教材匯入草稿
     * 一門課可有多份；發布後才寫入正式教材
     */
    public function up(): void
    {
        Schema::create('material_drafts', function (Blueprint $table) {
            $table->id()->comment('草稿 ID');

            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

            $table->string('name')->comment('教材名稱');

            $table->string('status')
                ->default('draft')
                ->comment('draft、published 或 archived');

            $table->json('tree')
                ->comment('主題／章節／單元／知識卡樹');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_drafts');
    }
};
