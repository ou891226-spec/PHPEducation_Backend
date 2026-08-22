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
        $account = $this->generateAccount($application->email);
        $password = $this->generatePassword();
        
        $teacher = Teacher::create([
            'name' => $application->name,
            'email' => $application->email,
            'account' => $account,
            'password' => Hash::make($password),
        ]);

        return [
            'tid' => $teacher->id,
            'account' => $account,
            'password' => $password,
        ];
    }

    /**
     * 依據 Email 自動生成預設帳號
     *
     * 規則：[使用者名稱]_[網域代碼]
     * 例如：test@gmail.com => test_g
     *       john@yahoo.com => john_y
     *
     * @param string $email
     * @return string
     */
    private function generateAccount(string $email): string
    {
        [$username, $domain] = explode('@', $email, 2);
        $domainName = explode('.', $domain)[0];

        $providerCode = match ($domainName) {
            'gmail' => 'g',
            'yahoo' => 'y',
            'hotmail' => 'h',
            'outlook' => 'o',
            default => substr($domainName, 0, 1),
        };

        return $username . '_' . $providerCode;
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
