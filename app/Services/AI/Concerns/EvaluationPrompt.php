<?php

namespace App\Services\AI\Concerns;

trait EvaluationPrompt
{
    /**
     * 組合向 AI 提問的 Prompt 內容
     *
     * @param  string  $question        題目完整敘述
     * @param  string  $rubric          SOLO 分類標準量規 (S1~S5)
     * @param  string  $referenceAnswer 教師/標準參考解答程式碼
     * @param  string  $studentAnswer   學生提交的程式碼
     * @param  string  $executionResult 學生程式碼執行結果 (JSON 字串)
     * @return string
     */
    public function buildPrompt(
        string $question,
        string $rubric,
        string $referenceAnswer, 
        string $studentAnswer,
        string $executionResult,
    ): string {
        $prompt = <<<PROMPT
        你是一個 PHP 程式設計課程的批改助理。

        請根據以下資訊：
        1. 題目
        2. SOLO 評量標準
        3. 參考答案
        4. 學生答案
        5. 執行結果

        判斷學生目前的解題程度，並提供文字形式的批改回饋。

        【題目】
        {$question}

        【SOLO 評量標準】
        {$rubric}

        【參考答案】
        {$referenceAnswer}

        【學生答案】
        {$studentAnswer}

        【執行結果】
        {$executionResult}

        請回傳以下 JSON 格式：
        {
            "solo": "S?",
            "feedback": "..."
        }

        規則：
        1. solo 只能是 S1 ~ S5。
        2. 請綜合學生答案與執行結果進行判斷。
        3. 不要只因為程式能執行或輸出正確，就直接判定為最高程度，如違反題目敘述請以最低程度判別。
        4. 請比較學生答案與參考答案所呈現的解題方式，並依照 SOLO 評量標準判斷。
        5. feedback 必須使用繁體中文，說明學生目前的解題表現與判定原因。
        6. 只能回傳 JSON，不要加入 Markdown、```json 或其他文字。
        PROMPT;

        return $prompt;
    }

    /**
     * 清理並解析 AI 回傳的 JSON 字串
     */
    protected function parseResponse(string $rawText): array
    {
        $text = trim($rawText);
        // 清理可能包含的 Markdown 程式碼區塊標記 (例如 ```json ... ```)
        $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text);
        $result = json_decode($cleanJson, true);

        if (!is_array($result) || !isset($result['solo'])) {
            return [
                'solo'     => 'S1',
                'feedback' => $text ?: 'AI 評估完成，但回應格式解析異常。',
            ];
        }

        return $result;
    }
}
