<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Services\StudentAccountService;
use App\Models\StudentApplications;
use App\Mail\StudentAccountCreated;


class StudentApprovementController extends Controller
{
    //
    public function approve(int $id, StudentAccountService $studentAccountService)
    {
        $application = StudentApplications::findOrFail($id);

        if($application->status !== 'pending') {
            return response()->json([
                'message' => 'This application has already been processed.',
            ], 422);
        }

        $studentData = $studentAccountService->approveApplication($application);

        Mail::to($application->teacher->email)->send(new StudentAccountCreated(
            teacherName: $application->teacher->name,
            className: $application->class_name,
            students: $studentData,
        ));

        return response()->json([
            'message' => 'Student account application approved.',
            'data' => [
                'application_id' => $application->id,
                'students' => $studentData,
            ],
        ], 200);
    }
    
}
