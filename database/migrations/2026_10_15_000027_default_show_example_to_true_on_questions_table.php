<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 學生作答時改由學生自己點按鈕決定要不要看知識卡程式範例，題目預設一律開放範例。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('show_example')->default(true)->comment('是否把知識卡範例給學生看')->change();
        });

        DB::table('questions')->update(['show_example' => true]);
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('show_example')->default(false)->comment('是否把知識卡範例給學生看')->change();
        });
    }
};
