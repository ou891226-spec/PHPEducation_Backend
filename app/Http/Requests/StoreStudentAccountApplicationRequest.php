<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentAccountApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'tid' => ['required', 'integer', 'exists:teachers,id'],

            'class_name' => ['required', 'string', 'max:255'],

            'students' => ['required', 'array', 'min:1', 'max:50'],

            'students.*.student_no' => ['required', 'string'],

            'students.*.name' => ['required', 'string'],

            'students.*.email' => ['required', 'email'],
        ];
    }
}
