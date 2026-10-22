<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $levels = [
            1 => [
                'title' => '預結構',
                'description' => '對於題意無法理解，亦無法推演程式執行的結果。',
            ],
            2 => [
                'title' => '單一結構',
                'description' => '嘗試解讀，但只推演出部分的結果，無法產出完整的結果。',
            ],
            3 => [
                'title' => '多結構',
                'description' => '對於多個子問題(例如function) 推演相關結果，但無法將其關聯性有系統地整合起來，產出完整的結果。',
            ],
            4 => [
                'title' => '關係型',
                'description' => '能解讀程式碼，整合相關知識，推演正確的結果。',
            ],
            5 => [
                'title' => '擴展抽象化',
                'description' => '可以在更高的抽象層次上概念化，並推廣到新的主題或領域。在這個階段，學生可以將課堂概念應用到現實生活中。',
            ],
        ];

        foreach ($levels as $level => $data) {
            DB::table('solo')
                ->where('level', $level)
                ->update([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        DB::table('solo')
            ->whereBetween('level', [1, 5])
            ->update([
                'title' => null,
                'description' => null,
                'updated_at' => now(),
            ]);
    }
};
