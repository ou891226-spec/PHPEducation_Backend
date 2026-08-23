<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 驗證教師提交學生帳號申請的請求資料
 */
class StoreStudentAccountApplicationRequest extends FormRequest
{
   
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            //
            'tid' => ['required', 'integer', 'exists:teachers,id'],

            'course_id' => ['required', 'integer', 'exists:courses,id'],

            'class_name' => ['required', 'string', 'max:255'],

            'students' => ['required', 'array', 'min:1', 'max:50'],

            'students.*.student_no' => ['required', 'string'],

            'students.*.name' => ['required', 'string'],
        ];
    }
}
