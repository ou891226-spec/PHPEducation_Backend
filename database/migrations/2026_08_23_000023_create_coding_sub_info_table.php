<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 實作題給AI用的參考答案
     */
    public function up(): void
    {
        Schema::create('coding_sub_info', function (Blueprint $table) {
            $table->id()->comment('補充資訊編號');
            $table->foreignId('question_id')->unique()->constrained('questions')->cascadeOnDelete()->comment('題目編號');
            $table->text('ref_answer')->comment('人類判斷要有的程式碼');
            $table->text('ref_output')->nullable()->comment('人類判斷要有的程式碼輸出結果');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coding_sub_info');
    }
};
