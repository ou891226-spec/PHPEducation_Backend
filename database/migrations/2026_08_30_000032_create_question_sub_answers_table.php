<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 填空／除錯／解讀的每一格正解（question_sub_answers 表）
     * 填空：（1）→ sub_id=1，不掃題幹
     */
    public function up(): void
    {
        if (Schema::hasTable('question_sub_answers')) {
            return;
        }

        Schema::create('question_sub_answers', function (Blueprint $table) {
            $table->id()->comment('子題正解編號');
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete()->comment('題目編號');
            $table->unsignedInteger('sub_id')->comment('格子編號或行號');
            $table->text('answer')->comment('這一格的標準答案');
            $table->text('description')->nullable()->comment('說明');
            $table->unsignedTinyInteger('solo')->default(2)->comment('出題配分；學生答錯時作答端記 1');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');

            $table->unique(['question_id', 'sub_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_sub_answers');
    }
};
