<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 有題目使用的知識卡可脫離教材樹（unit_id 為空），單元刪除時改 SET NULL 而非 cascade。
     */
    public function up(): void
    {
        $unitId = collect(Schema::getColumns('knowledge_cards'))
            ->firstWhere('name', 'unit_id');

        if (($unitId['nullable'] ?? false) === true) {
            return;
        }

        Schema::table('knowledge_cards', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
        });

        Schema::table('knowledge_cards', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->comment('所屬單元 ID；有題目使用而脫離教材樹時可為空')->change();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_cards', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
        });

        Schema::table('knowledge_cards', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable(false)->comment('所屬單元 ID')->change();
            $table->foreign('unit_id')->references('id')->on('units')->cascadeOnDelete();
        });
    }
};
