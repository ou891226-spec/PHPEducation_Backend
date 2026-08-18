<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 學生申請帳號表
     */
    public function up(): void
    {
        Schema::create('student_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->comment('教師編號');
            $table->string('class_name')->comment('申請班級');
            $table->string('status')->default('pending')->comment('申請狀態');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');

            $table->primary('id');
            $table->foreign('id')->references('id')->on('teachers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_applications');
    }
};
