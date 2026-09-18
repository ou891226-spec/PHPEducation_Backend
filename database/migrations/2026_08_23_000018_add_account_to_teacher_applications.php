<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_applications', function (Blueprint $table) {
            $table->string('account')->unique()->after('email')->comment('教師自訂帳號');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_applications', function (Blueprint $table) {
            $table->dropColumn('account');
        });
    }
};
