<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jobwork\PostFindJobworkRequest;
use App\Http\Requests\Jobwork\PostGiveJobworkRequest;
use App\Services\JobworkService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class JobworkController extends Controller
{
    public function __construct(
        protected JobworkService $jobworkService
    ) {
    }

    /**
     * Converter: Post to find job work (offer capacity).
     */
    public function postFind(PostFindJobworkRequest $request)
    {
        $user = $request->user();

        try {
            $result = $this->jobworkService->postFindJobwork(
                $request->validated(),
                $user->id
            );

            return Response::success('Jobwork (find) posted successfully', $result, null, HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            $statusCode = (int) $e->getCode();
            if ($statusCode < 400 || $statusCode >= 600) {
                $statusCode = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;
            }

            return Response::error(
                $e->getMessage(),
                [
                    'error_code' => 'JOBWORK_FIND_POST_FAILED',
                ],
                $statusCode
            );
        }
    }

    /**
     * Converter: Post to give job work (outsource to other converters).
     */
    public function postGive(PostGiveJobworkRequest $request)
    {
        $user = $request->user();

        try {
            $result = $this->jobworkService->postGiveJobwork(
                $request->validated(),
                $user->id
            );

            return Response::success('Jobwork (give) posted successfully', $result, null, HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            $statusCode = (int) $e->getCode();
            if ($statusCode < 400 || $statusCode >= 600) {
                $statusCode = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;
            }

            return Response::error(
                $e->getMessage(),
                [
                    'error_code' => 'JOBWORK_GIVE_POST_FAILED',
                ],
                $statusCode
            );
        }
    }
}

