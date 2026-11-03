<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 全站開關。學生登入預設開放，由管理員關閉或重開。
     */
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('student_login_enabled')->default(true)->comment('是否允許學生登入');
                $table->timestamps();
            });
        }

        if (DB::table('site_settings')->doesntExist()) {
            DB::table('site_settings')->insert([
                'student_login_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
