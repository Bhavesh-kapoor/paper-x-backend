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
            $filters = $request->only(['category', 'page', 'per_page']);
            $materials = $this->materialService->getMaterials($filters);
            
            // Extract pagination meta from paginated result
            $pagination = null;
            if ($materials instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
                $pagination = [
                    'current_page' => $materials->currentPage(),
                    'total' => $materials->total(),
                    'per_page' => $materials->perPage(),
                    'last_page' => $materials->lastPage(),
                ];
            }
            
            return Response::success("materials.fetch", MaterialResource::collection($materials), $pagination);
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
