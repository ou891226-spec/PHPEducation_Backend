<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 實作題：已知給學生看；請輸出與參考答案只給老師批改對照。AI 先不存。
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (! Schema::hasColumn('questions', 'starter_code')) {
                $table->text('starter_code')->nullable()->after('show_example')->comment('實作題已知條件（給學生看）');
            }
            if (! Schema::hasColumn('questions', 'expected_output')) {
                $table->text('expected_output')->nullable()->after('starter_code')->comment('實作題期望輸出（學生看不到）');
            }
            if (! Schema::hasColumn('questions', 'reference_answer')) {
                $table->text('reference_answer')->nullable()->after('expected_output')->comment('實作題參考答案（學生看不到）');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('questions', 'starter_code') ? 'starter_code' : null,
                Schema::hasColumn('questions', 'expected_output') ? 'expected_output' : null,
                Schema::hasColumn('questions', 'reference_answer') ? 'reference_answer' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
