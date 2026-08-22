<?php
namespace App\Services;
use App\Models\StudentApplications;
use App\Models\StudentApplicationItems;
use App\Models\Student;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Class StudentAccountService
 * 專責處理學生帳號申請主單建立與批次開通建立的服務
 */
class StudentAccountService
{
    /**
     * 建立學生帳號申請單（含明細項目）
     * 
     * 使用資料庫交易（DB::transaction）確保主單與所有學生明細均成功寫入
     *
     * @param string $tid 提出申請的教師 ID
     * @param array $data 包含 class_name 與 students 陣列名冊
     * @return StudentApplications
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

    /**
     * 審核並批次生成學生正式帳號
     *
     * 使用資料庫交易（DB::transaction）進行：
     * 1. 遍歷每位學生，生成 12 碼隨機密碼並新增至 students 資料表
     * 2. 收集所有學生的帳密名冊以供回傳與寄信
     *
     * @param StudentApplications $application
     * @return array 包含每位學生 sid, class_name, student_no, name, password, email 的陣列
     */
    public function approveApplication(StudentApplications $application): array
    {
        return DB::transaction(function () use ($application) {
            
            $studentData = [];

            foreach ($application->items as $item) {

                // 1. 遍歷每位學生，生成 12 碼隨機密碼並新增至 students 資料表
                $password = $this->generatePassword();

                $student = Student::create([
                    'class_name' => $application->class_name,
                    'student_no' => $item->student_no,
                    'name' => $item->name,
                    'password' => Hash::make($password),
                    'email' => $item->email,
                ]);

                // 2. 收集所有學生的帳密名冊以供回傳與寄信
                $studentData[] = [
                    'sid'=> $student->id,
                    'class_name' => $application->class_name,
                    'student_no' => $student->student_no,
                    'name' => $item->name,
                    'password' => $password,
                    'email' => $item->email,
                ];
            }

            return $studentData;
        });
    }

    /**
     * 生成 12 位元隨機字串作為初始預設密碼
     *
     * @return string
     */
    private function generatePassword(): string
    {
        return Str::random(12);
    }
}