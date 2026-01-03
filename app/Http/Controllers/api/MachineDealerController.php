<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteMachineDealerProfileRequest;
use App\Http\Requests\PostMachineRequest;
use App\Services\MachineDealerService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class MachineDealerController extends Controller
{
    public function __construct(
        protected MachineDealerService $machineDealerService
    ) {
    }

    public function completeProfile(CompleteMachineDealerProfileRequest $request)
    {
        try {
            $user = $request->user();
            $machineDealer = $this->machineDealerService->completeProfile($request->validated(), $user->id);

            return Response::success('Machine dealer profile completed successfully', $machineDealer, null, HttpResponse::HTTP_CREATED);
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
            $dashboard = $this->machineDealerService->getDashboard($user->id);

            return Response::success('Dashboard data retrieved successfully', $dashboard);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function postMachine(PostMachineRequest $request)
    {
        try {
            $user = $request->user();
            $result = $this->machineDealerService->postMachine($request->validated(), $user->id);

            return Response::success('Machine listing posted successfully', $result, null, HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function getActiveListings()
    {
        try {
            $user = request()->user();
            $listings = $this->machineDealerService->getActiveListings($user->id);

            return Response::success('Active listings retrieved successfully', ['listings' => $listings]);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function getActiveRequirements()
    {
        try {
            $filters = request()->only(['intent', 'urgency', 'machine_id', 'per_page']);
            $requirements = $this->machineDealerService->getActiveRequirements($filters);

            return Response::success('Active requirements retrieved successfully', $requirements);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
