<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 題目與知識卡多對多
     * 一題可對多張知識卡；闖關範圍之後再依知識卡所在單元／章節決定
     */
    public function up(): void
    {
        if (Schema::hasTable('question_knowledge_cards')) {
            return;
        }

        Schema::create('question_knowledge_cards', function (Blueprint $table) {
            $table->id()->comment('流水號');
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete()->comment('題目編號');
            $table->foreignId('knowledge_card_id')->constrained('knowledge_cards')->cascadeOnDelete()->comment('知識卡編號');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');

            $table->unique(['question_id', 'knowledge_card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_knowledge_cards');
    }
};
