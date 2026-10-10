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
 * 4. 在資料庫 Transaction 內更新 QuestionRecord 的 SOLO 等級並寫入 AiFeedback
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
        S1：只能辨識單一資訊，無法完成題目要求。
        S2：能處理部分資料，但程式邏輯不完整。
        S3：能完成基本的邏輯運算，但程式結構或邊界處理仍有部分問題。
        S4：能正確使用流程控制（如迴圈與判斷式），完整且正確完成題意要求。
        S5：能在正確完成題目的基礎上，展現更完整、靈活或有效率的解題策略。
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

        // 3. 呼叫 AI 進行診斷評估（若外部 API 不可用則優雅降級）
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

        // 解析 SOLO 等級 (S1~S5 轉為整數 1~5 存入 unsignedTinyInteger)
        $rawSolo = $aiResult['solo'] ?? 'S1';
        $soloLevel = (int) preg_replace('/\D/', '', (string) $rawSolo);
        if ($soloLevel < 1 || $soloLevel > 5) {
            $soloLevel = 1;
        }

        // 4. 資料庫交易確保作答紀錄更新與 AI 回饋儲存的一致性
        return DB::transaction(function () use ($record, $soloLevel, $aiResult) {
            $record->update([
                'solo' => $soloLevel,
            ]);

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
