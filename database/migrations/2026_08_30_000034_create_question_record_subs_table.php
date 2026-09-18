<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * question_record_subs：B 類每一格的學生作答
     */
    public function up(): void
    {
        if (Schema::hasTable('question_record_subs')) {
            return;
        }

        Schema::create('question_record_subs', function (Blueprint $table) {
            $table->id()->comment('子題作答編號');
            $table->foreignId('question_record_id')->constrained('question_records')->cascadeOnDelete()->comment('作答總表');
            $table->unsignedInteger('sub_id')->comment('對應 question_sub_answers.sub_id');
            $table->text('answer')->comment('學生這格的答案');
            $table->boolean('is_right')->default(false)->comment('這格是否答對');
            $table->unsignedTinyInteger('solo')->default(1)->comment('對了抄正解 solo，錯了為 1');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_record_subs');
    }
};
