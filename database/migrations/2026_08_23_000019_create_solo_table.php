<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SOLO 理解層級
     */
    public function up(): void
    {
        Schema::create('solo', function (Blueprint $table) {
            $table->string('id')->primary()->comment('SOLO 編碼');
            $table->string('title')->comment('SOLO 標題');
            $table->text('structure_info')->nullable()->comment('結構層級說明');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });

        $now = now();
        DB::table('solo')->insert([
            ['id' => 'S1', 'title' => '前結構', 'structure_info' => '未掌握題意或回答與題目無關', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'S2', 'title' => '單一結構', 'structure_info' => '只用到單一要點', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'S3', 'title' => '多元結構', 'structure_info' => '列出多個要點但尚未整合', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'S4', 'title' => '關聯結構', 'structure_info' => '能串連多個要點成整體', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'S5', 'title' => '延伸抽象', 'structure_info' => '能類推到新情境並抽象化', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('solo');
    }
};
