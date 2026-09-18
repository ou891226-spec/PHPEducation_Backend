<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bloom 能力層級
     */
    public function up(): void
    {
        Schema::create('bloom', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Bloom 編碼');
            $table->string('title')->comment('Bloom 標題');
            $table->text('cognition_info')->nullable()->comment('認知層級說明');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });

        $now = now();
        DB::table('bloom')->insert([
            ['id' => 'B1', 'title' => '記憶', 'cognition_info' => '回憶事實、名詞與基本概念', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'B2', 'title' => '理解', 'cognition_info' => '解釋、摘要並說明意義', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'B3', 'title' => '應用', 'cognition_info' => '在新情境使用程序或方法', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'B4', 'title' => '分析', 'cognition_info' => '拆解、比較並找出關係', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'B5', 'title' => '評鑑', 'cognition_info' => '依規準判斷與評論', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'B6', 'title' => '創造', 'cognition_info' => '重組、設計並產出新結構', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bloom');
    }
};
