<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
    }

    public function getDashboard(Request $request)
    {
        try {
            $user = $request->user();
            $role = $request->input('role', $user->primary_role); // Default to user's primary role
            
            // Validate role parameter
            $validRoles = ['dealer', 'machine-dealer', 'converter', 'brand'];
            if (!in_array($role, $validRoles)) {
                return Response::error(
                    'Invalid role parameter. Valid roles: ' . implode(', ', $validRoles),
                    null,
                    HttpResponse::HTTP_BAD_REQUEST
                );
            }

            $dashboard = $this->dashboardService->getDashboard($user->id, $role);

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

