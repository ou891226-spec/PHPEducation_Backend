<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 圖譜教材：知識卡加上主題、類型，內容改可存 HTML；
     * 同一張卡可掛多個單元（knowledge_card_unit）。舊資料從 unit_id 回填。
     */
    public function up(): void
    {
        Schema::table('knowledge_cards', function (Blueprint $table) {
            $table->foreignId('topic_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('topics')
                ->nullOnDelete()
                ->comment('所屬主題；覆蓋後脫離樹時仍用來對回同一張卡');
            $table->string('type')
                ->default('keyword')
                ->after('title')
                ->comment('知識卡類型，例如 keyword、function');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE knowledge_cards MODIFY content LONGTEXT NOT NULL COMMENT '知識卡內容'");
        }

        Schema::create('knowledge_card_unit', function (Blueprint $table) {
            $table->id()->comment('關聯 ID');
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete()->comment('單元 ID');
            $table->foreignId('knowledge_card_id')->constrained('knowledge_cards')->cascadeOnDelete()->comment('知識卡 ID');
            $table->timestamps();
            $table->unique(['unit_id', 'knowledge_card_id']);
        });

        $cards = DB::table('knowledge_cards')->orderBy('id')->get();
        foreach ($cards as $card) {
            if ($card->unit_id === null) {
                continue;
            }

            $unit = DB::table('units')->where('id', $card->unit_id)->first();
            if ($unit === null) {
                continue;
            }

            DB::table('knowledge_card_unit')->insert([
                'unit_id' => $unit->id,
                'knowledge_card_id' => $card->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $chapter = DB::table('chapters')->where('id', $unit->chapter_id)->first();
            if ($chapter !== null) {
                DB::table('knowledge_cards')->where('id', $card->id)->update([
                    'topic_id' => $chapter->topic_id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_card_unit');

        Schema::table('knowledge_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('topic_id');
            $table->dropColumn('type');
        });
    }
};
