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
        if (! Schema::hasTable('ai_feedback')) {
            Schema::create('ai_feedback', function (Blueprint $table) {
                $table->id()->comment('AI 回饋編號');
                $table->foreignId('question_record_id')->constrained('question_records')->cascadeOnDelete()->comment('紀錄編號');
                $table->longText('feedback_content')->comment('AI 的回饋內容 (JSON 或文字)');
                $table->timestamps();
            });
        } else {
            Schema::table('ai_feedback', function (Blueprint $table) {
                if (Schema::hasColumn('ai_feedback', 'record_id') && ! Schema::hasColumn('ai_feedback', 'question_record_id')) {
                    $table->renameColumn('record_id', 'question_record_id');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedback');
    }
};
