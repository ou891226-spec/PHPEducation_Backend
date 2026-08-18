<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 章節表
     */
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id()->comment('章節 ID');
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete()->comment('所屬主題 ID');
            $table->string('name')->comment('章節名稱');
            $table->integer('sort_order')->comment('排序順序');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
