<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Response as HttpResponse;

class PricingController extends Controller
{
    public function __construct(private readonly PricingService $pricingService)
    {
    }

    /**
     * Quote the posting fee for a draft inquiry (display before posting).
     * POST /api/v1/pricing/quote
     */
    public function quote(Request $request)
    {
        $validated = $request->validate([
            'role' => ['nullable', 'string'],
            'inquiry_type' => ['nullable', 'string'],
            'intent' => ['nullable', 'string'],
            'material_id' => ['nullable', 'integer', 'exists:materials,id'],
            'material_name' => ['nullable', 'string'],
            'material_category' => ['nullable', 'string'],
            'thickness' => ['nullable', 'numeric'],
            'thickness_unit' => ['nullable', 'string'],
            'size' => ['nullable', 'string'],
            'size_unit' => ['nullable', 'string'],
            'quantity' => ['nullable', 'numeric'],
            'quantity_unit' => ['nullable', 'string'],
            'urgency' => ['nullable', 'string'],
            'machine_price_range' => ['nullable', 'string'],
        ]);

        // Default the poster role from the authenticated user when not supplied.
        if (empty($validated['role']) && $request->user()) {
            $validated['role'] = $request->user()->primary_role;
        }

        $quote = $this->pricingService->quote($validated);

        return Response::success('pricing.quoted', $quote, null, HttpResponse::HTTP_OK);
    }
}
