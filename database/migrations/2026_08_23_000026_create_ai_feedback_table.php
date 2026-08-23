<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AI 對實作題的批改結果
     */
    public function up(): void
    {
        Schema::create('ai_feedback', function (Blueprint $table) {
            $table->id()->comment('AI 回饋編號');
            $table->foreignId('record_id')->constrained('question_records')->cascadeOnDelete()->comment('紀錄編號');
            $table->text('feedback_content')->comment('AI 的回饋內容');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedback');
    }
};
