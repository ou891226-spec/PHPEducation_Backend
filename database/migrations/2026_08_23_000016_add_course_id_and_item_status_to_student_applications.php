<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_applications', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->nullable()
                ->after('tid')
                ->constrained('courses')
                ->cascadeOnDelete()
                ->comment('課程 ID');
        });

        Schema::table('student_application_items', function (Blueprint $table) {
            $table->string('status')
                ->default('pending')
                ->after('email')
                ->comment('pending 或 approved');
        });
    }

    public function down(): void
    {
        Schema::table('student_application_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('student_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
        });
    }
};
