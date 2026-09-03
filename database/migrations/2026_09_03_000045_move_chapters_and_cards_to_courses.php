<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 主題就是課程：章節、知識卡改掛 course_id，不再使用 topics。
     */
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->nullable()
                ->after('id')
                ->constrained('courses')
                ->cascadeOnDelete()
                ->comment('所屬課程 ID');
        });

        Schema::table('knowledge_cards', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('courses')
                ->nullOnDelete()
                ->comment('所屬課程；脫離教材樹時仍用來對回同一張卡');
        });

        if (Schema::hasTable('topics') && Schema::hasColumn('chapters', 'topic_id')) {
            $topics = DB::table('topics')->get()->keyBy('id');

            foreach (DB::table('chapters')->orderBy('id')->get() as $chapter) {
                $topic = $topics->get($chapter->topic_id);
                if ($topic === null) {
                    continue;
                }

                DB::table('chapters')->where('id', $chapter->id)->update([
                    'course_id' => $topic->course_id,
                ]);
            }

            foreach (DB::table('knowledge_cards')->orderBy('id')->get() as $card) {
                $courseId = null;
                if ($card->topic_id !== null) {
                    $courseId = $topics->get($card->topic_id)?->course_id;
                }

                if ($courseId === null && $card->unit_id !== null) {
                    $unit = DB::table('units')->where('id', $card->unit_id)->first();
                    if ($unit !== null) {
                        $chapter = DB::table('chapters')->where('id', $unit->chapter_id)->first();
                        $courseId = $chapter?->course_id;
                    }
                }

                if ($courseId !== null) {
                    DB::table('knowledge_cards')->where('id', $card->id)->update([
                        'course_id' => $courseId,
                    ]);
                }
            }

            Schema::table('chapters', function (Blueprint $table) {
                $table->dropConstrainedForeignId('topic_id');
            });

            Schema::table('knowledge_cards', function (Blueprint $table) {
                $table->dropConstrainedForeignId('topic_id');
            });
        }

        Schema::dropIfExists('topics');
    }

    public function down(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id()->comment('主題 ID');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete()->comment('所屬課程 ID');
            $table->string('name')->comment('主題名稱');
            $table->integer('sort_order')->comment('排序順序');
            $table->timestamps();
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->foreignId('topic_id')
                ->nullable()
                ->after('id')
                ->constrained('topics')
                ->cascadeOnDelete();
        });

        Schema::table('knowledge_cards', function (Blueprint $table) {
            $table->foreignId('topic_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('topics')
                ->nullOnDelete();
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
        });

        Schema::table('knowledge_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
        });
    }
};
