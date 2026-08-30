<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 作答總表：錯 1、對 2；實作題老師批改寫 Bloom。
     */
    public function up(): void
    {
        Schema::table('question_records', function (Blueprint $table) {
            if (! Schema::hasColumn('question_records', 'solo')) {
                $table->unsignedTinyInteger('solo')->nullable()->after('teacher_status')->comment('錯 1、對 2');
            }

            if (! Schema::hasColumn('question_records', 'bloom_id')) {
                $table->string('bloom_id')->nullable()->after('solo')->comment('實作題老師批改的 Bloom');
                $table->foreign('bloom_id')->references('id')->on('bloom');
            }
        });

        if (Schema::hasColumn('question_records', 'solo')) {
            DB::table('question_records')->where('system_status', 'correct')->whereNull('solo')->update(['solo' => 2]);
            DB::table('question_records')->where('system_status', 'wrong')->whereNull('solo')->update(['solo' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('question_records', function (Blueprint $table) {
            if (Schema::hasColumn('question_records', 'bloom_id')) {
                $table->dropForeign(['bloom_id']);
                $table->dropColumn('bloom_id');
            }

            if (Schema::hasColumn('question_records', 'solo')) {
                $table->dropColumn('solo');
            }
        });
    }
};
