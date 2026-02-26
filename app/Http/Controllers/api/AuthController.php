<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OtpLoginRequest;
use App\Services\AuthService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AuthController extends Controller
{
    public $authService;

    // injecting the authservice to the authController
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * sendOtp
     *
     * @param  mixed $request
     * @return void
     */
    public function loginWithOtp(OtpLoginRequest $request)
    {
        try {
            $result = $this->authService->loginWithOtp(
                $request->mobile,
                $request->otp
            );
            
            // Ensure result is a plain array to avoid any model serialization
            if (is_array($result)) {
                return Response::success("auth.otp.success", $result);
            }
            
            return Response::success("auth.otp.success", $result);
        } catch (\Exception $e) {
            // Catch morph map errors specifically
            if (str_contains($e->getMessage(), 'morph map')) {
                \Log::error('Morph map error during OTP verification: ' . $e->getMessage());
                return Response::error(
                    'Authentication error. Please try again.',
                    null,
                    HttpResponse::HTTP_INTERNAL_SERVER_ERROR
                );
            }
            
            $statusCode = method_exists($e, 'getStatusCode')
                ? $e->getStatusCode()
                : ((int) $e->getCode() >= 100 && (int) $e->getCode() < 600
                    ? (int) $e->getCode()
                    : HttpResponse::HTTP_BAD_REQUEST);

            return Response::error(
                $e->getMessage(),
                null,
                $statusCode
            );
        }
    }
}
