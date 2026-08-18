<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 教師申請帳號表
     */
    public function up(): void
    {
        Schema::create('teacher_applications', function (Blueprint $table) {
            $table->id()->comment('申請 ID');
            $table->string('name')->comment('教師名稱');
            $table->string('email')->unique()->comment('教師信箱');
            $table->string('reason')->comment('申請理由');
            $table->string('status')->default('pending')->comment('申請狀態');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_applications');
    }
};
