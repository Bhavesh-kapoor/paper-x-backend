<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->alias([
            'token.exists' => \App\Http\Middleware\EnsureTokenExist::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle authentication errors
        $exceptions->render(function (AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'errors' => [
                        'error_code' => 'AUTHENTICATION_REQUIRED',
                        'auth' => 'You must be authenticated to access this resource'
                    ]
                ], 401);
            }
            
            // Redirect admin routes to admin login
            if ($request->is('admin/*')) {
                return redirect()->route('admin.login');
            }
        });
        
        // Handle authorization errors (AccessDeniedHttpException)
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $user = $request->user();
                return response()->json([
                    'success' => false,
                    'message' => 'This action is unauthorized.',
                    'errors' => [
                        'error_code' => 'AUTHORIZATION_FAILED',
                        'user_id' => $user->id ?? null,
                        'user_roles' => $user ? [
                            'has_brand' => $user->brand ? true : false,
                            'has_converter' => $user->converter ? true : false,
                            'has_dealer' => $user->dealer ? true : false,
                            'has_machine_dealer' => $user->machineDealer ? true : false,
                        ] : null,
                        'message' => $e->getMessage() ?: 'You do not have permission to perform this action.',
                        'hint' => 'Please ensure you have completed the required profile and have the necessary permissions.',
                    ]
                ], 403);
            }
        });
    })->create();
