<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RTD\CreateProductRequest;
use App\Http\Requests\RTD\UpdateProductRequest;
use App\Http\Resources\RTD\RtdProductResource;
use App\Exceptions\RTDDomainException;
use App\Services\RTDProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class RTDProductController extends Controller
{
    public function __construct(
        protected RTDProductService $productService,
    ) {
    }

    public function store(CreateProductRequest $request)
    {
        try {
            $product = $this->productService->createProduct(
                $request->validated(),
                $request->user()->id
            );

            return Response::success(
                'Product created successfully',
                new RtdProductResource($product),
                null,
                HttpResponse::HTTP_CREATED
            );
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function update(UpdateProductRequest $request, int $id)
    {
        try {
            $product = $this->productService->updateProduct(
                $id,
                $request->validated(),
                $request->user()->id
            );

            return Response::success('Product updated successfully', new RtdProductResource($product));
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function pause(int $id)
    {
        try {
            $product = $this->productService->pauseProduct($id, request()->user()->id);

            return Response::success('Product paused', new RtdProductResource($product));
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function resume(int $id)
    {
        try {
            $product = $this->productService->resumeProduct($id, request()->user()->id);

            return Response::success('Product resumed', new RtdProductResource($product));
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function myProducts(Request $request)
    {
        try {
            $products = $this->productService->getConverterProducts(
                $request->user()->id,
                $request->only(['status', 'category', 'per_page'])
            );

            return Response::success(
                'Converter products',
                RtdProductResource::collection($products),
            );
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function catalog(Request $request)
    {
        try {
            $products = $this->productService->browseCatalog(
                $request->only([
                    'category', 'lead_time', 'delivery_geography',
                    'min_price', 'max_price',
                    'min_moq', 'max_moq',
                    'has_branding', 'location_scope',
                    'sort_by', 'sort_dir', 'per_page',
                ]),
                $request->user()
            );

            return Response::success('RTD Catalog', RtdProductResource::collection($products));
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function show(int $id)
    {
        try {
            $product = \App\Models\RtdProduct::with('priceSlabs', 'converter')->findOrFail($id);

            return Response::success('Product detail', new RtdProductResource($product));
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_NOT_FOUND);
        }
    }
}
