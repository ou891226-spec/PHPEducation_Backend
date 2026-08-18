<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 學生表
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id()->comment('學生 ID');
            $table->string('password')->comment('加密後的密碼');
            $table->string('student_no')->unique()->comment('學號');
            $table->string('name')->comment('學生姓名');
            $table->string('email')->unique()->comment('學校信箱（登入帳號）');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
