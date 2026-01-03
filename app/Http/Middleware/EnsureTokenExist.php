<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as FacadesResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenExist
{

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (!$token) {
            return FacadesResponse::error('Unauthenticated', [
                "errors" => [
                    'token' => 'Token is missing'
                ]
            ], 401);
        }
        // if token is comming then verify it from the personal access token table 
        $exist = PersonalAccessToken::findToken($token);
        if (!$exist) {
            return FacadesResponse::error('Unauthenticated', [
                "errors" => [
                    'token' => 'Invalid token'
                ]
            ], 401);
        }
        return $next($request);
    }
}
