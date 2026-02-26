<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use App\Models\Material;
use App\Models\MaterialGrade;
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

    /**
     * Create a custom material (when user can't find theirs in the list).
     * Requires auth. Uses firstOrCreate to avoid duplicates.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $category = $validated['category'] ?? 'Custom';
        $material = Material::firstOrCreate(
            [
                'name' => trim($validated['name']),
                'category' => $category,
            ]
        );

        // Ensure material has at least one grade (required by app)
        if ($material->grades()->count() === 0) {
            MaterialGrade::create([
                'material_id' => $material->id,
                'name' => 'Standard',
            ]);
        }

        $material->load('grades');
        return Response::success('material.created', new MaterialResource($material), null, HttpResponse::HTTP_CREATED);
    }

    public function getMaterials(Request $request)
    {
        try {
            $filters = $request->only(['category', 'page', 'per_page']);
            $materials = $this->materialService->getMaterials($filters);
            
            // Extract pagination meta from paginated result
            $pagination = null;
            if ($materials instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
                $currentPage = $materials->currentPage();
                $lastPage = $materials->lastPage();
                $pagination = [
                    'current_page' => $currentPage,
                    'total' => $materials->total(),
                    'per_page' => $materials->perPage(),
                    'last_page' => $lastPage,
                    'total_pages' => $lastPage,
                    'total_items' => $materials->total(),
                    'has_next' => $currentPage < $lastPage,
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
