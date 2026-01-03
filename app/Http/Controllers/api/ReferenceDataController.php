<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Material;
use App\Models\MaterialFinish;
use App\Models\MaterialMill;
use App\Models\MaterialThicknessType;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response as HttpResponse;

class ReferenceDataController extends Controller
{
    /**
     * Get all machines
     * GET /api/v1/machines
     */
    public function getMachines(Request $request)
    {
        try {
            $query = Machine::query();

            // Filter by type if provided
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by category
            if ($request->has('category')) {
                $query->where('type', $request->category);
            }

            $machines = $query->orderBy('name')->get();

            return Response::success('Machines retrieved successfully', $machines);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get material finishes (grades/coatings)
     * GET /api/v1/material-finishes?material_id=1
     */
    public function getMaterialFinishes(Request $request)
    {
        try {
            $query = MaterialFinish::query();

            // Filter by material_id if provided
            if ($request->has('material_id')) {
                $query->where('material_id', $request->material_id);
            }

            // Filter by type if provided
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $finishes = $query->orderBy('name')->get();

            return Response::success('Material finishes retrieved successfully', $finishes);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get mills/brands for a specific material
     * GET /api/v1/material-mills?material_id=1
     */
    public function getMaterialMills(Request $request)
    {
        try {
            $materialId = $request->input('material_id');

            if (!$materialId) {
                return Response::error('material_id is required', null, HttpResponse::HTTP_BAD_REQUEST);
            }

            $mills = MaterialMill::where('material_id', $materialId)
                ->with('brand')
                ->get()
                ->map(function ($mill) {
                    return [
                        'id' => $mill->brand_id,
                        'name' => $mill->brand->name,
                        'material_id' => $mill->material_id,
                    ];
                });

            return Response::success('Material mills retrieved successfully', $mills);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get thickness types/units for a specific material
     * GET /api/v1/material-thickness-types?material_id=1
     */
    public function getMaterialThicknessTypes(Request $request)
    {
        try {
            $materialId = $request->input('material_id');

            if (!$materialId) {
                return Response::error('material_id is required', null, HttpResponse::HTTP_BAD_REQUEST);
            }

            $thicknessTypes = MaterialThicknessType::where('material_id', $materialId)
                ->orderBy('is_primary', 'desc')
                ->orderBy('unit')
                ->get()
                ->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'unit' => $type->unit,
                        'is_primary' => $type->is_primary,
                        'material_id' => $type->material_id,
                    ];
                });

            return Response::success('Material thickness types retrieved successfully', $thicknessTypes);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get all brands/mills (for material mills)
     * GET /api/v1/brands
     * Note: This returns mill brands (paper mills), not user brand profiles
     */
    public function getBrands(Request $request)
    {
        try {
            // Check if user_id column exists in brands table
            $hasUserIdColumn = DB::getSchemaBuilder()->hasColumn('brands', 'user_id');
            
            $query = DB::table('brands');
            
            if ($hasUserIdColumn) {
                // New structure: only get mill brands (user_id is NULL)
                $query->whereNull('user_id');
            }
            
            // Select name column (mill brands have 'name', user brands have 'company_name')
            if (DB::getSchemaBuilder()->hasColumn('brands', 'name')) {
                $query->select('id', 'name');
            } else {
                // Fallback if name doesn't exist
                $query->select('id', 'company_name as name');
            }
            
            $brands = $query->orderBy('name')->get();

            return Response::success('Brands (mills) retrieved successfully', $brands);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get complete material details with all related data
     * GET /api/v1/materials/{id}/details
     */
    public function getMaterialDetails($materialId)
    {
        try {
            $material = Material::with(['grades'])->findOrFail($materialId);

            $mills = MaterialMill::where('material_id', $materialId)
                ->with('brand')
                ->get()
                ->map(function ($mill) {
                    return [
                        'id' => $mill->brand_id,
                        'name' => $mill->brand->name,
                    ];
                });

            $finishes = MaterialFinish::where('material_id', $materialId)
                ->orderBy('name')
                ->get();

            $thicknessTypes = MaterialThicknessType::where('material_id', $materialId)
                ->orderBy('is_primary', 'desc')
                ->orderBy('unit')
                ->get();

            return Response::success('Material details retrieved successfully', [
                'material' => [
                    'id' => $material->id,
                    'name' => $material->name,
                    'category' => $material->category,
                ],
                'mills' => $mills,
                'finishes' => $finishes,
                'thickness_types' => $thicknessTypes,
            ]);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
