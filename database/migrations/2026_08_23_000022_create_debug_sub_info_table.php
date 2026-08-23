<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 除錯題的答案資訊
     */
    public function up(): void
    {
        Schema::create('debug_sub_info', function (Blueprint $table) {
            $table->id()->comment('除錯題補充資訊編號');
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete()->comment('題目編號');
            $table->integer('code_line')->comment('錯誤程式碼的目標行數');
            $table->text('answer')->comment('正解');
            $table->text('description')->nullable()->comment('該除錯題目描述');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debug_sub_info');
    }
};
