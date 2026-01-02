<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ProfileUpdateRequest;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Response as HttpResponse;

class UserController extends Controller
{
    public $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * getProfile
     *
     * @param  mixed $request
     * @return void
     */
    public function getProfile(Request $request)
    {
        try {
            return Response::success("user.profile_fetch", $this->userService->getProfile());
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


    public function updateProfile(ProfileUpdateRequest $request)
    {
        try {
            return Response::success("user.profile_update", $this->userService->updateProfile($request->validated()));
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
