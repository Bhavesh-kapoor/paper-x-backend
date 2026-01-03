<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteBrandProfileRequest;
use App\Services\BrandService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class BrandController extends Controller
{
    public function __construct(
        protected BrandService $brandService
    ) {
    }

    public function completeProfile(CompleteBrandProfileRequest $request)
    {
        try {
            $user = $request->user();
            $brand = $this->brandService->completeProfile($request->validated(), $user->id);

            return Response::success('Brand profile completed successfully', $brand, null, HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function getDashboard()
    {
        try {
            $user = request()->user();
            $dashboard = $this->brandService->getDashboard($user->id);

            return Response::success('Dashboard data retrieved successfully', $dashboard);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
