<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 選擇／是非：正解 solo=2，其餘 solo=1
     */
    public function up(): void
    {
        if (! Schema::hasColumn('question_options', 'solo')) {
            Schema::table('question_options', function (Blueprint $table) {
                $table->unsignedTinyInteger('solo')->default(1)->after('is_answer')->comment('正解 2、其餘 1');
            });
        }

        DB::table('question_options')->where('is_answer', true)->update(['solo' => 2]);
        DB::table('question_options')->where('is_answer', false)->update(['solo' => 1]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('question_options', 'solo')) {
            Schema::table('question_options', function (Blueprint $table) {
                $table->dropColumn('solo');
            });
        }
    }
};
