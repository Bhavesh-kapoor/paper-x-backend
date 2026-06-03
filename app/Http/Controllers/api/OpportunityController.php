<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnsuresUserMatches;
use App\Http\Requests\Dealer\AcceptOpportunityRequest;
use App\Http\Requests\Dealer\DeclineOpportunityRequest;
use App\Services\OpportunityService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class OpportunityController extends Controller
{
    use EnsuresUserMatches;

    public function __construct(
        protected OpportunityService $opportunityService
    ) {
    }

    public function getOpportunities()
    {
        try {
            $user = request()->user();

            // Backfill matches for users who registered after inquiries were posted.
            $this->ensureUserMatches($user);

            $filters = request()->only(['page', 'per_page']);
            $opportunities = $this->opportunityService->getOpportunities($user->id, $filters);

            return Response::success('opportunity.list', $opportunities['opportunities'], $opportunities['pagination'] ?? null);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function getOpportunityDetails(int $inquiryId)
    {
        try {
            $user = request()->user();
            $details = $this->opportunityService->getOpportunityDetails($inquiryId, $user->id);

            return Response::success('opportunity.details', $details);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function acceptOpportunity(int $id, AcceptOpportunityRequest $request)
    {
        try {
            $user = $request->user();
            $result = $this->opportunityService->acceptOpportunity($id, $user->id);

            return Response::success('opportunity.accepted', $result);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function declineOpportunity(int $id, DeclineOpportunityRequest $request)
    {
        try {
            $user = $request->user();
            $result = $this->opportunityService->declineOpportunity($id, $user->id, $request->input('reason'));

            return Response::success('opportunity.declined', $result);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}




