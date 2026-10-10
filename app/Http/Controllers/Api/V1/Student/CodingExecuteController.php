<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Utils\CodeExecutionUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 程式即時自測執行控制器
 *
 * 提供學生在作答過程中即時執行程式碼、測試標準輸入 (STDIN) 並獲取輸出結果
 */
class CodingExecuteController extends Controller
{
    /**
     * 執行學生提交的程式碼並回傳執行結果
     *
     * @param  Request  $request
     *         - code: string (學生寫的 PHP 程式碼)
     *         - input: string|null (欲傳入 STDIN 的自訂測資)
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        $code = (string) ($request->input('code') ?? '');
        $input = (string) ($request->input('input') ?? '');

        $result = CodeExecutionUtil::execute($code, $input);

        return response()->json($result);
    }
}
