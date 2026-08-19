<?php
 
namespace App\Services;
use App\Models\Teacher;
use App\Models\TeacherApplication;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class TeacherAccountService
{
    /**
     * Create a new class instance.
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

    // 生成帳號
    private function generateAccount(string $email): string
    {
        // 從 email 中提取使用者名稱和網域 Ex: test@gmail.com => test_g
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

    // 生成密碼
    private function generatePassword(): string
    {
        return Str::random(12);
    }
}
