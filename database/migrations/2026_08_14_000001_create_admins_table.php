<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 管理員表
     * name 固定顯示 "系統管理員"
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id()->comment('管理員 ID');
            $table->string('account')->unique()->comment('登入帳號');
            $table->string('password')->comment('加密後的密碼');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
