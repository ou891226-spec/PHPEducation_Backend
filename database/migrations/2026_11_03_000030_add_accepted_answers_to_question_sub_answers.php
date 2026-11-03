<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 填空／除錯同一格除了標準答案，還可以登記其他也算對的字串。
     */
    public function up(): void
    {
        if (! Schema::hasTable('question_sub_answers') || Schema::hasColumn('question_sub_answers', 'accepted_answers')) {
            return;
        }

        Schema::table('question_sub_answers', function (Blueprint $table) {
            $table->json('accepted_answers')->nullable()->after('answer')->comment('同一格也可接受的其他答案字串');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('question_sub_answers', 'accepted_answers')) {
            return;
        }

        Schema::table('question_sub_answers', function (Blueprint $table) {
            $table->dropColumn('accepted_answers');
        });
    }
};
