<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnsuresUserMatches;
use App\Http\Requests\CompleteConverterProfileRequest;
use App\Http\Requests\Converter\PostRequirementRequest;
use App\Http\Requests\PostMachineRequest;
use App\Http\Requests\SubmitResponseRequest;
use App\Services\ConverterService;
use App\Services\OpportunityService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class ConverterController extends Controller
{
    use EnsuresUserMatches;

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

            // Backfill matches for users who registered after inquiries were posted.
            $this->ensureUserMatches($user);

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

            // Backfill matches for users who registered after inquiries were posted.
            $this->ensureUserMatches($user);

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

    public function postRequirement(PostRequirementRequest $request)
    {
        try {
            $user = $request->user();
            $inquiry = $this->converterService->postRequirement($request->validated(), $user->id);

            return Response::success('Requirement posted successfully', $inquiry, null, HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Post machine buy/sell requirement (converter).
     * Same payload as machine-dealer; creates inquiry with poster_type=converter, no MachineListing.
     */
    public function postMachine(PostMachineRequest $request)
    {
        try {
            $user = $request->user();
            $result = $this->converterService->postMachine($request->validated(), $user->id);

            return Response::success('Machine requirement posted successfully', $result, null, HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
