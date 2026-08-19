<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Services\TeacherAccountService;
use App\Models\TeacherApplication;
use App\Mail\TeacherAccountCreated;


class TeacherApprovementController extends Controller
{
    /**
     * Create a new class instance.
     */
    public function approve(int $id, TeacherAccountService $teacherAccountService)
    {
        $application = TeacherApplication::findOrFail($id);

        if($application->status !== 'pending') {
            return response()->json([
                'message' => 'This application has already been processed.',
            ], 422);
        }

        $result = $teacherAccountService->createFromApplication($application);

        $application->update([
                'status' => 'approved',
        ]);

        Mail::to($application->email)->send(new TeacherAccountCreated(
            tid: $result['tid'],
            name: $application->name,
            account: $result['account'],
            password: $result['password'],
        ));

        return response()->json([
            'message' => 'Teacher application approved.',
            'data' => [
                'tid' => $result['tid'],
                'name' => $application->name,
                'email' => $application->email,
                'account' => $result['account'],
                'password' => $result['password'],
            ],
        ], 200);
    }    
}
