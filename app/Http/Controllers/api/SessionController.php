<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\SessionService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class SessionController extends Controller
{
    public function __construct(
        protected SessionService $sessionService
    ) {
    }

    public function getSession(int $sessionId)
    {
        try {
            $user = request()->user();
            $session = $this->sessionService->getSession($sessionId, $user->id);

            return Response::success('session.details', $session);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function getHistory()
    {
        try {
            $user = request()->user();
            $filters = request()->only(['page', 'per_page']);
            $history = $this->sessionService->getHistory($user->id, $filters);

            return Response::success('session.history', $history['history'], $history['pagination'] ?? null);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}




