<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 教師表
     */
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id()->comment('教師 ID');

            $table->string('account')->unique()->comment('登入帳號');
            $table->string('password')->comment('加密後的密碼');
            $table->string('name')->comment('教師姓名');
            $table->string('email')->unique()->comment('Email');
            
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
