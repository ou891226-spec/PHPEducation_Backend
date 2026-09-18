<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 這題的 Bloom + SOLO 組合（一題可以有多組）
     */
    public function up(): void
    {
        Schema::create('question_bloom_solo_mappings', function (Blueprint $table) {
            $table->id()->comment('問題與 B&S 映射編號');
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete()->comment('題目編號');
            $table->string('bloom_id')->comment('Bloom 編碼');
            $table->string('solo_id')->comment('SOLO 編碼');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');

            $table->foreign('bloom_id')->references('id')->on('bloom');
            $table->foreign('solo_id')->references('id')->on('solo');
            $table->unique(['question_id', 'bloom_id', 'solo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bloom_solo_mappings');
    }
};
