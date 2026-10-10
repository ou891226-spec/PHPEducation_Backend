<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\CodingGradingJob;
use App\Models\QuestionRecord;
use App\Services\CodingGradingService;
use App\Utils\CodeExecutionUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 相容控制器：相容 PHPEducation_AI 原有 API 路由
 */
class CodingGradingCompatibilityController extends Controller
{
    public function __construct(
        private readonly CodingGradingService $codingGradingService,
    ) {}

    /**
     * 即時自測執行
     */
    public function execute(Request $request): JsonResponse
    {
        $code = (string) ($request->input('code') ?? '');
        $input = (string) ($request->input('input') ?? '');

        $result = CodeExecutionUtil::execute($code, $input);

        return response()->json($result);
    }

    /**
     * 送出作答並派發 AI 批改
     */
    public function submit(Request $request): JsonResponse
    {
        $code = (string) ($request->input('code') ?? '');
        $questionId = (int) $request->input('question_id', 1);
        $studentId = $request->user()?->id ?? 1;

        $record = QuestionRecord::create([
            'student_id'     => $studentId,
            'question_id'    => $questionId,
            'result'         => $code,
            'system_status'  => QuestionRecord::STATUS_PENDING,
            'teacher_status' => QuestionRecord::STATUS_PENDING,
            'solo'           => null,
        ]);

        // CodingGradingJob::dispatch($record);

        return response()->json([
            'message'   => '作答已送出，AI 正在分析批改中',
            'record_id' => $record->id,
            'status'    => 'pending',
        ], 201);
    }

    /**
     * 查詢批改進度與結果
     */
    public function show(int $recordId): JsonResponse
    {
        $record = QuestionRecord::with('aiFeedback')->findOrFail($recordId);

        if (!$record->solo && !$record->aiFeedback) {
            return response()->json([
                'record_id' => $record->id,
                'completed' => false,
                'status'    => 'processing',
            ]);
        }

        $feedbackContent = $record->aiFeedback 
            ? (json_decode($record->aiFeedback->feedback_content, true) ?? $record->aiFeedback->feedback_content)
            : null;

        return response()->json([
            'record_id' => $record->id,
            'completed' => true,
            'status'    => 'completed',
            'solo'      => $record->solo,
            'feedback'  => $feedbackContent,
        ]);
    }

    /**
     * 觸發 / 重新執行 AI 批改
     */
    public function grade(int $recordId): JsonResponse
    {
        $record = QuestionRecord::findOrFail($recordId);

        CodingGradingJob::dispatch($record);

        return response()->json([
            'message'   => '批改已加入背景處理',
            'record_id' => $record->id,
        ]);
    }
}
