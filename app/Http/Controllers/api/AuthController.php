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
            return Response::success("auth.otp.success", $this->authService->loginWithOtp(
                $request->mobile,
                $request->otp
            ));
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode')
                ? $e->getStatusCode()
                : HttpResponse::HTTP_BAD_REQUEST
            );

        }
    }
}
