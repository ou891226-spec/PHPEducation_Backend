<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 刪題目時一併刪作答紀錄（題目可直接刪，不再因有作答而拒絕）。
     */
    public function up(): void
    {
        Schema::table('question_records', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->foreign('question_id')
                ->references('id')
                ->on('questions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('question_records', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->foreign('question_id')
                ->references('id')
                ->on('questions');
        });
    }
};
