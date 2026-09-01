<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 老師決定要不要把知識卡 example 給學生看。預設不放。
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (! Schema::hasColumn('questions', 'show_example')) {
                $table->boolean('show_example')->default(false)->after('description')->comment('是否把知識卡範例給學生看');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'show_example')) {
                $table->dropColumn('show_example');
            }
        });
    }
};
