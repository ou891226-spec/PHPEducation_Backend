<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_application_items', function (Blueprint $table) {
            $table->string('email')->nullable()->after('name')->comment('信箱（開通前可先指定）');
        });

        $rows = DB::table('student_application_items')
            ->where(function ($query) {
                $query->whereNull('email')->orWhere('email', '');
            })
            ->get(['id', 'student_no']);

        foreach ($rows as $row) {
            $studentNo = ltrim((string) $row->student_no, 'Ss');
            DB::table('student_application_items')
                ->where('id', $row->id)
                ->update([
                    'email' => 's'.$studentNo.'@nutc.edu.tw',
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('student_application_items', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
