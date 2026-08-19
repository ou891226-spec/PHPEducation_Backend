<?php
namespace App\Services;
use App\Models\StudentApplications;
use App\Models\StudentApplicationItems;
use App\Models\Student;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentAccountService
{
    /**
     * Create a new class instance.
     */
    public function createApplication(string $tid, array $data): StudentApplications
    {
        return DB::transaction(function () use ($tid, $data) {
            $application =  StudentApplications::create([
                'tid' => $tid,
                'class_name' => $data['class_name'],
                'status' => 'pending',
            ]);

            foreach ($data['students'] as $studentData) {
                StudentApplicationItems::create([
                    'application_id' => $application->id,
                    'student_no' => $studentData['student_no'],
                    'name' => $studentData['name'],
                    'email' => $studentData['email'],
                ]);
            }

            return $application;
        });     
        
    }

    public function approveApplication(StudentApplications $application): array
    {
        return DB::transaction(function () use ($application) {
            
            $studentData = [];

            foreach ($application->items as $item) {

                $password = $this->generatePassword();

                $student = Student::create([
                    'class_name' => $application->class_name,
                    'student_no' => $item->student_no,
                    'name' => $item->name,
                    'password' => Hash::make($password),
                    'email' => $item->email,
                ]);

                $studentData[] = [
                    'sid'=> $student->id,
                    'class_name' => $application->class_name,
                    'student_no' => $student->student_no,
                    'name' => $item->name,
                    'password' => $password,
                    'email' => $item->email,
                ];
            }

            $application->update(['status' => 'approved']);
            return $studentData;

        });
    }

    // 生成密碼
    private function generatePassword(): string
    {
        return Str::random(12);
    }
}