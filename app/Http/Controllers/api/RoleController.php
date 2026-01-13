<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\SwitchRoleRequest;
use App\Models\User;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class RoleController extends Controller
{
    public function switchRole(SwitchRoleRequest $request)
    {
        try {
            $user = $request->user();
            $requestedRole = $request->input('role');

            // Validate user has this role
            $hasRole = false;
            if ($user->primary_role === $requestedRole) {
                $hasRole = true;
            } elseif ($user->has_secondary_role && $user->secondary_role === $requestedRole) {
                $hasRole = true;
            }

            if (!$hasRole) {
                return Response::error(
                    'You do not have access to this role',
                    null,
                    HttpResponse::HTTP_FORBIDDEN
                );
            }

            // Switch roles (swap if needed)
            if ($user->primary_role !== $requestedRole) {
                $oldPrimary = $user->primary_role;
                $user->update([
                    'primary_role' => $requestedRole,
                    'secondary_role' => $oldPrimary,
                ]);
            }

            // Return dashboard config based on role
            $dashboardConfig = [
                'role' => $requestedRole,
                'dashboard_type' => strtolower($requestedRole),
            ];

            return Response::success('role.switched', $dashboardConfig);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}





