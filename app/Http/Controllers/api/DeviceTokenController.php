<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\DeviceTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class DeviceTokenController extends Controller
{
    public function __construct(
        protected DeviceTokenService $deviceTokenService
    ) {
    }

    /**
     * Register / refresh the caller's FCM device token.
     * POST /api/v1/user/device-tokens
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => ['required', 'string', 'max:255'],
                'platform' => ['nullable', Rule::in(['ios', 'android', 'web'])],
            ]);

            $this->deviceTokenService->register(
                $request->user(),
                $validated['token'],
                $validated['platform'] ?? null
            );

            return Response::success('device_token.registered', null);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Unregister a device token (logout / notifications disabled).
     * DELETE /api/v1/user/device-tokens
     */
    public function destroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => ['required', 'string', 'max:255'],
            ]);

            $this->deviceTokenService->unregister($validated['token']);

            return Response::success('device_token.unregistered', null);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
