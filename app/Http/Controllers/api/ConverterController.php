<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteConverterProfileRequest;
use App\Http\Requests\SubmitResponseRequest;
use App\Services\ConverterService;
use App\Services\OpportunityService;
use Illuminate\Http\Request;
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

    public function getRequirements(Request $request)
    {
        try {
            $user = $request->user();
            $filters = $request->only(['city', 'requirement_type', 'urgency', 'per_page', 'page']);
            $result = $this->converterService->getRequirements($user->id, $filters);

            return Response::success('Requirements retrieved successfully', $result);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function respondToRequirement(SubmitResponseRequest $request, int $inquiryId)
    {
        try {
            $user = $request->user();
            $result = $this->converterService->respondToRequirement($inquiryId, $user->id, $request->validated());

            return Response::success('Response submitted successfully', $result, null, HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
