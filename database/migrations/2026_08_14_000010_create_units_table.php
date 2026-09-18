<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 單元表
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id()->comment('單元 ID');
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete()->comment('所屬章節 ID');
            $table->string('name')->comment('單元名稱');
            $table->integer('sort_order')->comment('排序順序');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
