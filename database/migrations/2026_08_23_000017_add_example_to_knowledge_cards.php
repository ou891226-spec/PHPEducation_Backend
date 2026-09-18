<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_cards', function (Blueprint $table) {
            $table->text('example')
                ->nullable()
                ->after('content')
                ->comment('知識卡範例');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_cards', function (Blueprint $table) {
            $table->dropColumn('example');
        });
    }
};
