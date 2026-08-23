<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 驗證教師提交帳號申請的請求資料
 */
class StoreTeacherApplicationRequest extends FormRequest
{
  
    public function authorize(): bool
    {
        return true; // 這裡可以根據需求進行授權邏輯的判斷
    }

    public function rules(): array
    {
        return [
            //
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'account' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'nullable', 'string'],
        ];
    }
}
