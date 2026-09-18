<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 選擇題的選項
     */
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->id()->comment('選項編號');
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete()->comment('題目編號');
            $table->string('title')->comment('選項標題');
            $table->text('description')->nullable()->comment('選項描述');
            $table->boolean('is_answer')->default(false)->comment('是否為正確答案');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
