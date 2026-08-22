<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentAccountApplicationRequest;
use App\Services\StudentAccountService;

/**
 * Class StudentAccountApplicationController
 * 負責處理教師提交「整班學生帳號申請名冊」的控制器
 */
class StudentAccountApplicationController extends Controller
{
    
    public function __construct(
        private StudentAccountService $studentAccountService,
    ){}

    /**
     * 提交班級學生帳號開通申請
     *
     * @param StoreStudentAccountApplicationRequest $request 包含 教師ID、班級名稱、學生名單 陣列
     * @return JsonResponse
     */
    public function store(
        StoreStudentAccountApplicationRequest $request,
    )
    {

        $validatedData = $request->validated();

        $application = $this->studentAccountService->createApplication(
            $validatedData['tid'],
            $validatedData,
        );

        return response()->json([
            'message' => 'Student account application submitted successfully.',
            'data' => $application,
        ], 201);
    }
    
}
