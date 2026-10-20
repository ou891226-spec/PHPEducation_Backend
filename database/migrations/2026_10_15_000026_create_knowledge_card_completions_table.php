<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 學生完成知識卡學習的紀錄（即學即測全對，或沒有測驗題時按「我已讀完」）；
     * 作答紀錄加上來源，區分題目練習與即學即測。
     */
    public function up(): void
    {
        Schema::create('knowledge_card_completions', function (Blueprint $table) {
            $table->id()->comment('流水號');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete()->comment('學生 ID');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete()->comment('課程 ID');
            $table->foreignId('knowledge_card_id')->constrained('knowledge_cards')->cascadeOnDelete()->comment('知識卡 ID');
            $table->string('method', 20)->comment('quiz：即學即測全對；read：無測驗題，已讀完');
            $table->timestamp('completed_at')->comment('完成時間');
            $table->timestamps();

            $table->unique(['student_id', 'course_id', 'knowledge_card_id'], 'card_completion_unique');
        });

        Schema::table('question_records', function (Blueprint $table) {
            $table->string('source', 20)->default('practice')->after('question_id')->comment('practice：題目練習；quiz：知識卡即學即測');
        });
    }

    public function down(): void
    {
        Schema::table('question_records', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::dropIfExists('knowledge_card_completions');
    }
};
