<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentLoginOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() instanceof Student && ! SiteSetting::studentLoginEnabled()) {
            return response()->json([
                'statusCode' => 403,
                'message' => '學生登入已關閉',
            ], 403);
        }

        return $next($request);
    }
}
