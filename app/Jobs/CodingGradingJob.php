<?php

namespace App\Jobs;

use App\Models\QuestionRecord;
use App\Services\CodingGradingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * 程式作答 AI 批改背景佇列任務
 *
 * 實作 ShouldQueue 介面，將耗時的「程式沙盒執行 + AI 診斷」非同步處理
 * 避免阻塞前端 HTTP 請求
 */
class CodingGradingJob implements ShouldQueue
{
    use Queueable;

    /**
     * 此 Job 的最大執行時間限制 (秒)
     */
    public int $timeout = 60;

    /**
     * 建立 Job 實例
     *
     * @param  QuestionRecord  $record 欲批改的學生作答紀錄
     */
    public function __construct(
        public QuestionRecord $record,
    ) {}

    /**
     * 執行背景任務：呼叫批改服務完成評分與資料庫儲存
     */
    public function handle(CodingGradingService $codingGradingService): void
    {
        $codingGradingService->grade($this->record);
    }
}
