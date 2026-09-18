<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 判別使用登入者的身份
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id()->comment('Token ID');
            $table->string('tokenable_type')->comment('Token 所屬模型類型');
            $table->unsignedBigInteger('tokenable_id')->comment('Token 所屬模型 ID');
            $table->index(['tokenable_type', 'tokenable_id']);
            $table->string('name')->comment('Token 名稱');
            $table->string('token', 64)->unique()->comment('Token 雜湊值');
            $table->text('abilities')->nullable()->comment('權限範圍');
            $table->timestamp('last_used_at')->nullable()->comment('最後使用時間');
            $table->timestamp('expires_at')->nullable()->comment('過期時間');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
