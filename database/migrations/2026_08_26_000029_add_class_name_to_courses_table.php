<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 課程所屬班級
     * 開課時填寫，學生匯入時沿用
     */
    public function up(): void
    {
        if (Schema::hasColumn('courses', 'class_name')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->string('class_name')
                ->nullable()
                ->after('semester')
                ->comment('開課班級');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('class_name');
        });
    }
};
