<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 忘記密碼的一次性重設連結
     * 只存 token 的 SHA-256，原始 token 只出現在寄出的信裡
     */
    public function up(): void
    {
        Schema::create('password_reset_links', function (Blueprint $table) {
            $table->id();
            $table->string('user_type', 20)->comment('student 或 teacher');
            $table->unsignedBigInteger('user_id');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['user_type', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_links');
    }
};
