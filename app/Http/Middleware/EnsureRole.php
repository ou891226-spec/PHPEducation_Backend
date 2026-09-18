<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Student;
use App\Models\Teacher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $role = $this->resolveRole($user);

        if ($role === null || ! in_array($role, $roles, true)) {
            return response()->json([
                'statusCode' => 403,
                'message' => 'Forbidden',
            ], 403);
        }

        return $next($request);
    }

    private function resolveRole(mixed $user): ?string
    {
        return match (true) {
            $user instanceof Admin => 'admin',
            $user instanceof Teacher => 'teacher',
            $user instanceof Student => 'student',
            default => null,
        };
    }
}
