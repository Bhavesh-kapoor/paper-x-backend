<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\PostRequirementRequest;
use App\Http\Requests\CompleteBrandProfileRequest;
use App\Services\BrandService;
use App\Services\ChatService;
use Illuminate\Http\Request;
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

    public function postRequirement(PostRequirementRequest $request)
    {
        try {
            $user = $request->user();
            $result = $this->brandService->postRequirement($request->validated(), $user->id);

            return Response::success('Requirement posted successfully', $result, null, HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function getMyInquiries(Request $request)
    {
        try {
            $user = $request->user();
            $filters = $request->only(['status', 'urgency', 'per_page', 'page']);
            $result = $this->brandService->getMyInquiries($user->id, $filters);

            return Response::success('Inquiries retrieved successfully', $result);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function getMessages(Request $request, int $sessionId)
    {
        try {
            $user = $request->user();
            $chatService = app(ChatService::class);
            $messages = $chatService->getMessages($sessionId, $user->id);

            return Response::success('Messages retrieved successfully', $messages);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
