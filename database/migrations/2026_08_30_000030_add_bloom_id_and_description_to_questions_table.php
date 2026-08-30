<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 出題時指定 Bloom；description 為題目說明（questions 表）
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (! Schema::hasColumn('questions', 'bloom_id')) {
                $table->string('bloom_id')->nullable()->after('question_content')->comment('出題時的 Bloom 層級');
                $table->foreign('bloom_id')->references('id')->on('bloom');
            }

            if (! Schema::hasColumn('questions', 'description')) {
                $table->text('description')->nullable()->after('bloom_id')->comment('題目說明');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'bloom_id')) {
                $table->dropForeign(['bloom_id']);
                $table->dropColumn('bloom_id');
            }

            if (Schema::hasColumn('questions', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
