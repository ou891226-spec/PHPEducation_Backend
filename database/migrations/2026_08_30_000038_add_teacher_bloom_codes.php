<?php

use App\Models\Bloom;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * B11、B13、B42：第一碼 Bloom 1–6，第二碼 SOLO 1–3。
     */
    public function up(): void
    {
        $now = now();
        $levels = DB::table('bloom')
            ->whereIn('id', ['B1', 'B2', 'B3', 'B4', 'B5', 'B6'])
            ->get()
            ->keyBy('id');

        $rows = [];
        foreach (range(1, 6) as $bloom) {
            $base = $levels->get('B'.$bloom);
            if ($base === null) {
                continue;
            }

            foreach (range(1, 3) as $solo) {
                $id = 'B'.$bloom.$solo;
                if (DB::table('bloom')->where('id', $id)->exists()) {
                    continue;
                }

                $rows[] = [
                    'id' => $id,
                    'title' => Bloom::teacherChoiceTitle($id),
                    'cognition_info' => $base->cognition_info,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('bloom')->insert($rows);
        }
    }

    public function down(): void
    {
        $ids = [];
        foreach (range(1, 6) as $bloom) {
            foreach (range(1, 3) as $solo) {
                $ids[] = 'B'.$bloom.$solo;
            }
        }

        DB::table('bloom')->whereIn('id', $ids)->delete();
    }
};
