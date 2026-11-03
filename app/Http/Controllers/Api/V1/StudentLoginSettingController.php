<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentLoginSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'student_login_enabled' => SiteSetting::studentLoginEnabled(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);
        $enabled = (bool) $validated['enabled'];

        SiteSetting::current()->update([
            'student_login_enabled' => $enabled,
        ]);

        if (! $enabled) {
            Student::query()->each(function (Student $student): void {
                $student->tokens()->delete();
            });
        }

        return response()->json([
            'message' => $enabled ? '已開放學生登入' : '已關閉學生登入',
            'student_login_enabled' => $enabled,
        ]);
    }
}
