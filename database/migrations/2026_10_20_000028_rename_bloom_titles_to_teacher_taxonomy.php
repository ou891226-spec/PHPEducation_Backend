<?php

use App\Models\Bloom;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Bloom 改成：B11～B43（記憶瞭解／應用／分析／評價 × 事實／概念／程序）。
     * B5x、B6x 舊碼不刪，已用到的題目由老師編輯時重選。
     */
    public function up(): void
    {
        $now = now();

        foreach (Bloom::LEVELS as $level => $title) {
            DB::table('bloom')->where('id', 'B'.$level)->update([
                'title' => $title,
                'updated_at' => $now,
            ]);
        }

        // 舊的 cognition_info 是錯誤分類的說明，清掉以免下拉選單顯示錯的提示
        foreach (Bloom::teacherChoiceIds() as $id) {
            DB::table('bloom')->updateOrInsert(
                ['id' => $id],
                ['title' => Bloom::teacherChoiceTitle($id), 'cognition_info' => null, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        // 舊標題是錯的分類，不還原
    }
};
