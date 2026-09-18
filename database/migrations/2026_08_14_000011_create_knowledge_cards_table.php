<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 知識卡表
     */
    public function up(): void
    {
        Schema::create('knowledge_cards', function (Blueprint $table) {
            $table->id()->comment('知識卡 ID');
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete()
                ->comment('所屬單元 ID；有題目使用而脫離教材樹時可為空');
            $table->string('title')->comment('知識卡標題');
            $table->text('content')->comment('知識卡內容');
            $table->integer('sort_order')->comment('排序順序');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_cards');
    }
};
