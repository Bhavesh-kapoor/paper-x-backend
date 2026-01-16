<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\ProfileCompleteRequest;
use App\Http\Requests\Dealer\PostRequirementRequest;
use App\Services\DealerService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class DealerController extends Controller
{
    public function __construct(
        protected DealerService $dealerService
    ) {
    }

    public function completeProfile(ProfileCompleteRequest $request)
    {
        try {
             $user = $request->user();
            $dealer = $this->dealerService->completeProfile($request->validated(), $user->id);

            return Response::success('dealer.profile_complete', $dealer, null, HttpResponse::HTTP_CREATED);
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
            $dashboard = $this->dealerService->getDashboard($user->id);

            return Response::success('dealer.dashboard', $dashboard);
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
            $inquiry = $this->dealerService->postRequirement($request->validated(), $user->id);

            return Response::success('Requirement posted successfully', $inquiry, null, HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function getRequirements()
    {
        try {
            $user = request()->user();
            $filters = request()->only([
                'inquiry_type',
                'intent',
                'status',
                'urgency',
                'material_id',
                'machine_id',
                'sort_by',
                'sort_order',
                'per_page',
                'page'
            ]);
            
            $requirements = $this->dealerService->getRequirements($filters, $user->id);

            return Response::success('Requirements retrieved successfully', $requirements['requirements'], $requirements['pagination']);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}





