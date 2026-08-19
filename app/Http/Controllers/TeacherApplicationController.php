<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreTeacherApplicationRequest;
use App\Models\TeacherApplication;
use App\Models\Teacher;

class TeacherApplicationController extends Controller
{
    //
    public function store(StoreTeacherApplicationRequest $request)
    {
        // 
        // 驗證請求資料
        $validatedData = $request->validated();

        // 已經是教師
        $isTeacher = Teacher::where('email', $validatedData['email'])->exists();
        
        if ($isTeacher) {
            return response()->json([
                'message' => 'This email is already registered as a teacher.'
            ], 422);
        }
        
        // 已經有待審核申請
        $hasPendingApplication = TeacherApplication::where('email', $validatedData['email'])
            ->where('status', 'pending')
            ->exists();
            
        if ($hasPendingApplication) {
            return response()->json([
                'message' => 'You have a pending teacher application.'
            ], 422);
        }

        $teacherApplication = TeacherApplication::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'reason' => $validatedData['reason'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Teacher application submitted successfully.',
            'data' => $teacherApplication,
        ], 201);
    }
}
