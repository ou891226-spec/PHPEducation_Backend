<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 題目本身
     * 掛在單元下，與知識卡同層
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id()->comment('題目編號');
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete()->comment('所屬單元 ID');
            $table->foreignId('teacher_id')->constrained('teachers')->comment('建立教師 ID');
            $table->string('title')->comment('題目標題');
            $table->string('type')->comment('choice、debug 或 coding');
            $table->text('question_content')->comment('題目內容');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
