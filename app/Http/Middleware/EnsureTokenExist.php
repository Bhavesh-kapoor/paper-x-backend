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
            return FacadesResponse::error('Token Missing', [
                "errors" => [
                    'token' => 'Token is missing'
                ]
            ], 404);
        }
        // if token is comming then verify it from the personal access token table 
        $exist = PersonalAccessToken::findToken($token);
        if (!$exist) {
            return FacadesResponse::error('Invalid Token', [
                "errors" => [
                    'token' => 'Invalid Token'
                ]
            ], 401);
        }
        return $next($request);

    }
}
