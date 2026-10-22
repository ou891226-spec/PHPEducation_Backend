<?php

namespace App\Services;

use App\Models\AiFeedback;
use App\Models\QuestionRecord;
use App\Services\AI\AiProviderInterface;
use App\Utils\CodeExecutionUtil;
use Illuminate\Support\Facades\DB;

/**
 * 程式作答 AI 批改與評量服務
 *
 * 核心業務邏輯：
 * 1. 取得作答紀錄與題目內容（含 starter_code, expected_output, reference_answer）
 * 2. 透過 CodeExecutionUtil 在沙盒環境執行學生程式碼取得標準輸出與錯誤
 * 3. 呼叫 AI Provider 進行 SOLO 認知診斷 (S1~S5) 與繁體中文回饋
 * 4. 將 AI 回饋（含建議 SOLO）存入 ai_feedback，不覆寫正式 SOLO
 */
class CodingGradingService
{
    public function __construct(
        private readonly AiProviderInterface $aiProvider,
    ) {}

    /**
     * 執行批改流程
     *
     * @param  QuestionRecord  $record 欲批改的作答紀錄
     * @return array<string, mixed> 批改完成的回傳資料
     */
    public function grade(QuestionRecord $record): array
    {
        $record->loadMissing(['question', 'student']);
        $question = $record->question;

        $rubric = <<<RUBRIC
        S1(預結構)：對於題意無法理解，亦無法推演程式執行的結果。
        S2(單一結構)：嘗試解讀，但只推演出部分的結果，無法產出完整的結果。
        S3(多結構)：對於多個子問題(例如function) 推演相關結果，但無法將其關聯性有系統地整合起來，產出完整的結果。
        S4(關係型)：能解讀程式碼，整合相關知識，推演正確的結果。
        S5(擴展抽象化)：可以在更高的抽象層次上概念化，並推廣到新的主題或領域。在這個階段，學生可以將課堂概念應用到現實生活中。

        RUBRIC;

        $studentCode = (string) $record->result;

        // 1. 執行學生程式碼獲取沙盒執行結果
        $executionResult = CodeExecutionUtil::execute(
            $studentCode,
            $input = ''
        );

        // 2. 取得題目參考答案與題目說明
        $referenceAnswer = $question?->reference_answer ?? '';
        $questionContent = $question?->question_content ?? 'PHP 程式實作題';
        if ($question?->expected_output) {
            $questionContent .= "\n【期望輸出】：\n" . $question->expected_output;
        }

        // 3. 呼叫 AI 進行診斷評估
        try {
            $aiResult = $this->aiProvider->evaluate(
                $questionContent,
                $rubric,
                $referenceAnswer,
                $studentCode,
                json_encode($executionResult, JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AI grading evaluation failed: ' . $e->getMessage());
            $aiResult = [
                'solo' => 'S1',
                'feedback' => 'AI 評估服務暫時不可用，已將您的程式碼送交教師覆核。',
            ];
        }


        // 4. 將 AI 回饋（含建議 SOLO）存入 ai_feedback，不覆寫正式 SOLO
        return DB::transaction(function () use ($record, $aiResult) {

            $feedback = AiFeedback::updateOrCreate(
                [
                    'question_record_id' => $record->id,
                ],
                [
                    'feedback_content' => json_encode($aiResult, JSON_UNESCAPED_UNICODE),
                ]
            );

            return [
                'question_record_id' => $record->id,
                'solo'               => $record->solo,
                'ai_feedback_id'     => $feedback->id,
                'feedback_content'   => $aiResult,
            ];
        });
    }
}
