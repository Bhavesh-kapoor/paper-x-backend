<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use App\Services\MaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Response as HttpResponse;

class MaterialController extends Controller
{
    protected $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    public function getMaterials(Request $request)
    {
        try {
            $materials = $this->materialService->getMaterials($request->all());
            return Response::success("materials.fetch", MaterialResource::collection($materials));
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode')
                ? $e->getStatusCode()
                : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
