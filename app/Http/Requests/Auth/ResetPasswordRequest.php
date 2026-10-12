<?php

namespace App\Http\Requests\Auth;

use App\Services\AuthService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 忘記密碼：以重設連結設定新密碼的請求驗證器
 */
class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in([AuthService::RESET_TYPE_STUDENT, AuthService::RESET_TYPE_TEACHER])],
            'token' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }
}
