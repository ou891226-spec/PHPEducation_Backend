<?php

namespace App\Services\AI;

interface AiProviderInterface
{
    /**
     * 呼叫 AI 進行程式作答評估
     *
     * @param  string  $question        題目完整敘述
     * @param  string  $rubric          SOLO 分類標準量規 (S1~S5)
     * @param  string  $referenceAnswer 教師/標準參考解答程式碼
     * @param  string  $studentAnswer   學生提交的程式碼
     * @param  string  $executionResult 學生程式碼執行結果 (JSON 字串)
     * @return array [ 'solo' => 'S1'~'S5', 'feedback' => '繁中評語' ]
     */
    public function evaluate(
        string $question,
        string $rubric,
        string $referenceAnswer, 
        string $studentAnswer,
        string $executionResult,
    ): array;
}
