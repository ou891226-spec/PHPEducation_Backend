<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 清表。
     * 不動 courses／教材／knowledge_cards／questions／question_options／question_records。
     */
    public function up(): void
    {
        Schema::dropIfExists('ai_feedback');
        Schema::dropIfExists('coding_sub_info');
        Schema::dropIfExists('debug_sub_info');

        if (Schema::hasTable('question_records') && Schema::hasColumn('question_records', 'question_mapping_id')) {
            Schema::table('question_records', function (Blueprint $table) {
                $table->dropForeign(['question_mapping_id']);
                $table->dropColumn('question_mapping_id');
            });
        }

        Schema::dropIfExists('question_bloom_solo_mappings');
        Schema::dropIfExists('solo');
    }

    public function down(): void
    {
        // 舊表不再還原
    }
};
