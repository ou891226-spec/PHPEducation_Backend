<?php
 
namespace App\Services;
use App\Models\Teacher;
use App\Models\TeacherApplication;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Class TeacherAccountService
 * 專責處理教師帳號生成與密碼邏輯的核心服務
 */
class TeacherAccountService
{
    /**
     * 依據核准的教師申請單建立正式教師帳號
     *
     * @param TeacherApplication $application
     * @return array{tid: int, account: string, password: string} 回傳建立之教師 ID、帳號與明文初始密碼
     */
    public function createFromApplication(TeacherApplication $application): array
    {
        $account = $application->account;
        $password = $this->generatePassword();
        
        $teacher = Teacher::create([
            'name' => $application->name,
            'email' => $application->email,
            'account' => $account,
            'password' => $password,
        ]);

        return [
            'tid' => $teacher->id,
            'account' => $account,
            'password' => $password,
        ];
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
