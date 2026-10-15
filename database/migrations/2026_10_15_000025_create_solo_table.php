<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SOLO 層級定義（S1–S5）。
     */
    public function up(): void
    {
        Schema::create('solo', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->primary()->comment('SOLO 層級 1–5，對應各表 solo 欄位');
            $table->string('code', 2)->unique()->comment('S1–S5');
            $table->string('title')->nullable()->comment('層級名稱');
            $table->text('description')->nullable()->comment('層級說明');
            $table->timestamps();
        });

        $now = now();
        DB::table('solo')->insert(array_map(fn (int $level) => [
            'level' => $level,
            'code' => 'S'.$level,
            'title' => null,
            'description' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], range(1, 5)));
    }

    public function down(): void
    {
        Schema::dropIfExists('solo');
    }
};
