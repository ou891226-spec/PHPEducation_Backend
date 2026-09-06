<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 使用者修改密碼請求驗證器
 * 
 * 適用於已登入的管理者 (Admin)、教師 (Teacher) 與學生 (Student)
 */
class ChangePasswordRequest extends FormRequest
{
    /**
     * 判斷使用者是否有權限發送此請求
     * 透過 auth:sanctum 中介層保護，已登入使用者皆可存取
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 定義輸入欄位的驗證規則
     * 1. current_password：必填
     * 2. new_password：必填、至少 6 碼、需與 confirmation 欄位一致、不可與目前密碼相同
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ];
    }
}
