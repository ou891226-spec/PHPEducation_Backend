<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentAccountApplicationRequest;
use App\Services\StudentAccountService;

class StudentAccountApplicationController extends Controller
{
    //
    public function store(
        StoreStudentAccountApplicationRequest $request,
        StudentAccountService $studentAccountService
    )
    {
        // 驗證請求資料
        $validatedData = $request->validated();

        $application = $studentAccountService->createApplication(
            $validatedData['tid'],
            $validatedData,
        );

        return response()->json([
            'message' => 'Student account application submitted successfully.',
            'data' => $application,
        ], 201);
    }
    
}
