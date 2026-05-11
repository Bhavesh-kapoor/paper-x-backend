<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Material;
use App\Models\MaterialFinish;
use App\Models\MaterialMill;
use App\Models\MaterialThicknessType;
use App\Models\Brand;
use App\Models\ConverterType;
use App\Models\FinishedProduct;
use App\Models\ScrapType;
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
            $perPage = $request->input('per_page', 50);
            $query = Machine::query();

            // Filter by type if provided
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by category
            if ($request->has('category')) {
                $query->where('type', $request->category);
            }

            $machines = $query->orderBy('name')->paginate($perPage);

            $pagination = [
                'current_page' => $machines->currentPage(),
                'total' => $machines->total(),
                'per_page' => $machines->perPage(),
                'last_page' => $machines->lastPage(),
            ];

            return Response::success('Machines retrieved successfully', $machines->items(), $pagination);
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
            $perPage = $request->input('per_page', 50);
            $query = MaterialFinish::query();

            // Filter by material_id if provided
            if ($request->has('material_id')) {
                $query->where('material_id', $request->material_id);
            }

            // Filter by type if provided
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $finishes = $query->orderBy('name')->paginate($perPage);

            $pagination = [
                'current_page' => $finishes->currentPage(),
                'total' => $finishes->total(),
                'per_page' => $finishes->perPage(),
                'last_page' => $finishes->lastPage(),
            ];

            return Response::success('Material finishes retrieved successfully', $finishes->items(), $pagination);
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

            $perPage = $request->input('per_page', 50);

            $mills = MaterialMill::where('material_id', $materialId)
                ->with('brand')
                ->paginate($perPage);

            $millsData = $mills->map(function ($mill) {
                return [
                    'id' => $mill->brand_id,
                    'name' => $mill->brand->name,
                    'material_id' => $mill->material_id,
                ];
            });

            $pagination = [
                'current_page' => $mills->currentPage(),
                'total' => $mills->total(),
                'per_page' => $mills->perPage(),
                'last_page' => $mills->lastPage(),
            ];

            return Response::success('Material mills retrieved successfully', $millsData->toArray(), $pagination);
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

            $perPage = $request->input('per_page', 50);

            $thicknessTypes = MaterialThicknessType::where('material_id', $materialId)
                ->orderBy('is_primary', 'desc')
                ->orderBy('unit')
                ->paginate($perPage);

            $thicknessTypesData = $thicknessTypes->map(function ($type) {
                return [
                    'id' => $type->id,
                    'unit' => $type->unit,
                    'is_primary' => $type->is_primary,
                    'material_id' => $type->material_id,
                ];
            });

            $pagination = [
                'current_page' => $thicknessTypes->currentPage(),
                'total' => $thicknessTypes->total(),
                'per_page' => $thicknessTypes->perPage(),
                'last_page' => $thicknessTypes->lastPage(),
            ];

            return Response::success('Material thickness types retrieved successfully', $thicknessTypesData->toArray(), $pagination);
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
            $perPage = $request->input('per_page', 50);
            
            // Get only mill brands where name is NOT NULL
            // Mill brands have 'name' field, user brand profiles have 'name' as NULL
            $query = DB::table('brands')
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->select('id', 'name')
                ->orderBy('name');
            
            $total = $query->count();
            $currentPage = $request->input('page', 1);
            $lastPage = (int) ceil($total / $perPage);
            $offset = ($currentPage - 1) * $perPage;
            
            $brands = $query->offset($offset)
                ->limit($perPage)
                ->get();

            $pagination = [
                'current_page' => (int) $currentPage,
                'total' => $total,
                'per_page' => (int) $perPage,
                'last_page' => $lastPage,
            ];

            return Response::success('Brands (mills) retrieved successfully', $brands->toArray(), $pagination);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get all brand types
     * GET /api/v1/brand-types
     */
    public function getBrandTypes(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 50);
            $query = \App\Models\BrandType::query();

            // Filter by category if provided
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            $brandTypes = $query->orderBy('sort_order')
                ->orderBy('name')
                ->paginate($perPage);

            $brandTypesData = $brandTypes->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'category' => $type->category,
                ];
            });

            $pagination = [
                'current_page' => $brandTypes->currentPage(),
                'total' => $brandTypes->total(),
                'per_page' => $brandTypes->perPage(),
                'last_page' => $brandTypes->lastPage(),
            ];

            return Response::success('Brand types retrieved successfully', $brandTypesData->toArray(), $pagination);
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

    /**
     * Manually add a mill/brand for dealer registration
     * POST /api/v1/dealer/mill/add
     */
    public function addMillBrand(Request $request)
    {
        try {
            $request->validate([
                'mill_brand_id' => ['nullable', 'integer', 'exists:brands,id'],
                'mill_brand_name' => ['required_without:mill_brand_id', 'string', 'max:255'],
                'prefer_not_to_disclose' => ['nullable', 'boolean'],
                'relationship' => ['nullable', 'string', 'in:authorized-agent,independent-dealer'],
                'material_id' => ['nullable', 'exists:materials,id'],
            ]);

            // If mill_brand_id is provided, use existing brand
            if ($request->has('mill_brand_id') && $request->mill_brand_id) {
                $brand = DB::table('brands')->where('id', $request->mill_brand_id)->first();
                if (!$brand) {
                    return Response::error('Brand not found', null, HttpResponse::HTTP_NOT_FOUND);
                }
            } else {
                // Check if brand already exists by name
                // Try to find in original brands table (without user_id constraint)
                $hasUserIdColumn = DB::getSchemaBuilder()->hasColumn('brands', 'user_id');
                
                $query = DB::table('brands')->where('name', $request->mill_brand_name);
                if ($hasUserIdColumn) {
                    $query->whereNull('user_id');
                }
                $brand = $query->first();

                if (!$brand) {
                    // Create new mill brand
                    $brandData = [
                        'name' => $request->mill_brand_name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    // Only add user_id if column exists and it should be null for mill brands
                    if ($hasUserIdColumn) {
                        $brandData['user_id'] = null;
                    }
                    
                    $brandId = DB::table('brands')->insertGetId($brandData);
                    $brand = (object) ['id' => $brandId, 'name' => $request->mill_brand_name];
                }
            }

            // If material_id is provided, associate the brand with the material
            if ($request->has('material_id') && $request->material_id) {
                \App\Models\MaterialMill::firstOrCreate([
                    'material_id' => $request->material_id,
                    'brand_id' => $brand->id,
                ]);
            }

            // Map relationship to database format
            $agentType = null;
            if ($request->relationship === 'authorized-agent') {
                $agentType = 'AUTHORIZED_AGENT';
            } elseif ($request->relationship === 'independent-dealer') {
                $agentType = 'DEALER';
            }

            return Response::success('Mill brand added successfully', [
                'mill_brand_id' => $brand->id,
                'mill_brand_name' => $brand->name ?? $request->mill_brand_name,
                'prefer_not_to_disclose' => $request->prefer_not_to_disclose ?? false,
                'relationship' => $request->relationship ?? null,
                'agent_type' => $agentType, // For use in dealer material details
            ], null, HttpResponse::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error(
                'Validation failed',
                $e->errors(),
                HttpResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Manually add a finish for dealer registration
     * POST /api/v1/dealer/finish/add
     */
    public function addFinish(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'material_id' => ['nullable', 'exists:materials,id'],
                'type' => ['nullable', 'string', 'in:finish,coating,grade,variant,surface,treatment'],
            ]);

            // Check if finish already exists
            $finish = MaterialFinish::where('name', $request->name)
                ->when($request->has('material_id'), function ($query) use ($request) {
                    return $query->where('material_id', $request->material_id);
                })
                ->first();

            if (!$finish) {
                // Create new finish
                $finish = MaterialFinish::create([
                    'name' => $request->name,
                    'material_id' => $request->material_id ?? null,
                    'type' => $request->type ?? 'finish',
                ]);
            }

            return Response::success('Finish added successfully', [
                'id' => $finish->id,
                'name' => $finish->name,
                'material_id' => $finish->material_id,
                'type' => $finish->type,
            ], null, HttpResponse::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error(
                'Validation failed',
                $e->errors(),
                HttpResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get all converter types
     * GET /api/v1/converter-types
     * Returns a flat list of all converter types (ignoring categories/headers)
     */
    public function getConverterTypes(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 1000); // Large default to get all items
            $query = ConverterType::query();

            // Order by sort_order if available, otherwise by name
            if (DB::getSchemaBuilder()->hasColumn('converter_types', 'sort_order')) {
                $query->orderBy('sort_order')->orderBy('name');
            } else {
                $query->orderBy('name');
            }

            $converterTypes = $query->paginate($perPage);

            // Return simple list with id and name only (ignore categories)
            $typesData = $converterTypes->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                ];
            });

            $pagination = [
                'current_page' => $converterTypes->currentPage(),
                'total' => $converterTypes->total(),
                'per_page' => $converterTypes->perPage(),
                'last_page' => $converterTypes->lastPage(),
            ];

            return Response::success('Converter types retrieved successfully', $typesData->toArray(), $pagination);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get all finished products
     * GET /api/v1/finished-products
     * Returns a flat list of all finished products (ignoring categories/headers)
     */
    public function getFinishedProducts(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 1000); // Large default to get all items
            $query = FinishedProduct::query();

            // Order by sort_order if available, otherwise by name
            if (DB::getSchemaBuilder()->hasColumn('finished_products', 'sort_order')) {
                $query->orderBy('sort_order')->orderBy('name');
            } else {
                $query->orderBy('name');
            }

            $finishedProducts = $query->paginate($perPage);

            // Return simple list with id and name only (ignore categories)
            $productsData = $finishedProducts->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                ];
            });

            $pagination = [
                'current_page' => $finishedProducts->currentPage(),
                'total' => $finishedProducts->total(),
                'per_page' => $finishedProducts->perPage(),
                'last_page' => $finishedProducts->lastPage(),
            ];

            return Response::success('Finished products retrieved successfully', $productsData->toArray(), $pagination);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get all scrap types
     * GET /api/v1/scrap-types
     * Returns a flat list of all scrap types (ignoring categories/headers)
     */
    public function getScrapTypes(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 1000); // Large default to get all items
            $query = ScrapType::query();

            // Order by sort_order if available, otherwise by name
            if (DB::getSchemaBuilder()->hasColumn('scrap_types', 'sort_order')) {
                $query->orderBy('sort_order')->orderBy('name');
            } else {
                $query->orderBy('name');
            }

            $scrapTypes = $query->paginate($perPage);

            // Return simple list with id and name only (ignore categories)
            $typesData = $scrapTypes->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                ];
            });

            $pagination = [
                'current_page' => $scrapTypes->currentPage(),
                'total' => $scrapTypes->total(),
                'per_page' => $scrapTypes->perPage(),
                'last_page' => $scrapTypes->lastPage(),
            ];

            return Response::success('Scrap types retrieved successfully', $typesData->toArray(), $pagination);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get all converter reference data in a single response
     * GET /api/v1/converter-reference-data
     * Returns converter types, finished products, scrap types, and machines in one call
     */
    public function getConverterReferenceData(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 1000); // Large default to get all items

            // Get converter types
            $converterTypesQuery = ConverterType::query();
            if (DB::getSchemaBuilder()->hasColumn('converter_types', 'sort_order')) {
                $converterTypesQuery->orderBy('sort_order')->orderBy('name');
            } else {
                $converterTypesQuery->orderBy('name');
            }
            $converterTypes = $converterTypesQuery->paginate($perPage);
            $converterTypesData = $converterTypes->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                ];
            })->toArray();

            // Get finished products
            $finishedProductsQuery = FinishedProduct::query();
            if (DB::getSchemaBuilder()->hasColumn('finished_products', 'sort_order')) {
                $finishedProductsQuery->orderBy('sort_order')->orderBy('name');
            } else {
                $finishedProductsQuery->orderBy('name');
            }
            $finishedProducts = $finishedProductsQuery->paginate($perPage);
            $finishedProductsData = $finishedProducts->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category,
                    'description' => $product->description ?? null,
                ];
            })->toArray();

            // Get scrap types
            $scrapTypesQuery = ScrapType::query();
            if (DB::getSchemaBuilder()->hasColumn('scrap_types', 'sort_order')) {
                $scrapTypesQuery->orderBy('sort_order')->orderBy('name');
            } else {
                $scrapTypesQuery->orderBy('name');
            }
            $scrapTypes = $scrapTypesQuery->paginate($perPage);
            $scrapTypesData = $scrapTypes->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'category' => $type->category,
                    'description' => $type->description ?? null,
                ];
            })->toArray();

            // Get machines
            $machinesQuery = Machine::query();
            if ($request->has('type')) {
                $machinesQuery->where('type', $request->type);
            }
            if ($request->has('category')) {
                $machinesQuery->where('type', $request->category);
            }
            $machines = $machinesQuery->orderBy('name')->paginate($perPage);
            $machinesData = $machines->map(function ($machine) {
                return [
                    'id' => $machine->id,
                    'name' => $machine->name,
                    'type' => $machine->type,
                    'description' => $machine->description,
                ];
            })->toArray();

            return Response::success('Converter reference data retrieved successfully', [
                'converter_types' => $converterTypesData,
                'finished_products' => $finishedProductsData,
                'scrap_types' => $scrapTypesData,
                'machines' => $machinesData,
            ]);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Create a custom machine (when user can't find theirs in the list).
     * POST /api/v1/machines
     * Requires auth. Uses firstOrCreate to avoid duplicates by name.
     */
    public function storeMachine(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'type' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            $type = isset($validated['type']) ? trim($validated['type']) : null;
            $description = isset($validated['description']) ? trim($validated['description']) : null;

            $machine = Machine::firstOrCreate(
                ['name' => trim($validated['name'])],
                [
                    'type' => $type !== '' ? $type : null,
                    'description' => $description !== '' ? $description : null,
                ]
            );

            return Response::success('machine.created', [
                'id' => $machine->id,
                'name' => $machine->name,
                'type' => $machine->type,
                'description' => $machine->description,
            ], null, HttpResponse::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error(
                'Validation failed',
                $e->errors(),
                HttpResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Create a custom finished product (when user can't find theirs in the list).
     * POST /api/v1/finished-products
     * Requires auth. Uses firstOrCreate to avoid duplicates by name.
     */
    public function storeFinishedProduct(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'category' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            $category = isset($validated['category']) ? trim($validated['category']) : null;
            $description = isset($validated['description']) ? trim($validated['description']) : null;

            $product = FinishedProduct::firstOrCreate(
                ['name' => trim($validated['name'])],
                [
                    'category' => $category !== '' ? $category : null,
                    'description' => $description !== '' ? $description : null,
                ]
            );

            return Response::success('finished_product.created', [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'description' => $product->description,
            ], null, HttpResponse::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error(
                'Validation failed',
                $e->errors(),
                HttpResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Create a custom scrap type (when user can't find theirs in the list).
     * POST /api/v1/scrap-types
     * Requires auth. Uses firstOrCreate to avoid duplicates by name.
     */
    public function storeScrapType(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'category' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            $category = isset($validated['category']) ? trim($validated['category']) : null;
            $description = isset($validated['description']) ? trim($validated['description']) : null;

            $scrapType = ScrapType::firstOrCreate(
                ['name' => trim($validated['name'])],
                [
                    'category' => $category !== '' ? $category : null,
                    'description' => $description !== '' ? $description : null,
                ]
            );

            return Response::success('scrap_type.created', [
                'id' => $scrapType->id,
                'name' => $scrapType->name,
                'category' => $scrapType->category,
                'description' => $scrapType->description,
            ], null, HttpResponse::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error(
                'Validation failed',
                $e->errors(),
                HttpResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
