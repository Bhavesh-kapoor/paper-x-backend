<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteConverterProfileRequest;
use App\Services\ConverterService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class ConverterController extends Controller
{
    public function __construct(
        protected ConverterService $converterService
    ) {
    }

    public function completeProfile(CompleteConverterProfileRequest $request)
    {
        try {
            $user = $request->user();
            $converter = $this->converterService->completeProfile($request->validated(), $user->id);

            return Response::success('Converter profile completed successfully', $converter, null, HttpResponse::HTTP_CREATED);
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
            $dashboard = $this->converterService->getDashboard($user->id);

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
