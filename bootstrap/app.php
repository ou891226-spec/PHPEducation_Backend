<?php

use App\Exceptions\AccountNotActivatedException;
use App\Exceptions\UnauthorizedLoginException;
use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            return '/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'statusCode' => 401,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        $exceptions->render(function (UnauthorizedLoginException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'statusCode' => 401,
                    'message' => $e->getMessage(),
                ], 401);
            }
        });

        $exceptions->render(function (AccountNotActivatedException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'statusCode' => 403,
                    'message' => $e->getMessage(),
                ], 403);
            }
        });
    })->create();
