<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // 既有單元預設 published，避免學生端突然看不到教材
            $table->string('status', 20)
                ->default('published')
                ->after('sort_order')
                ->comment('draft=草稿（僅教師可見）、published=已開放給學生');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
