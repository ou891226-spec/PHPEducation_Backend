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
            $table->id();
            
            $table->foreignId('tid')
                ->constrained('teachers')
                ->cascadeOnDelete()
                ->comment('教師 ID');
                
            $table->string('class_name')->comment('班級名稱');
            $table->string('status')->default('pending')->comment('申請狀態');

            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_applications');
    }
};
