<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Exceptions\RTDDomainException;
use App\Services\RtdListingPackService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class RtdListingPackController extends Controller
{
    public function __construct(
        protected RtdListingPackService $packService,
    ) {
    }

    /**
     * GET list of available RTD listing packs.
     */
    public function index()
    {
        $packs = $this->packService->getAvailablePacks();
        return Response::success('Listing packs', $packs);
    }

    /**
     * POST purchase a listing pack. Body: { "pack_slug": "pack_5" }
     */
    public function purchase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pack_slug' => ['required', 'string', 'max:32'],
        ]);
        if ($validator->fails()) {
            return Response::error($validator->errors()->first(), null, HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $entitlement = $this->packService->purchasePack(
                $request->user()->id,
                $request->input('pack_slug')
            );
            $remaining = max(0, $entitlement->product_limit - $entitlement->used_count);
            return Response::success('Pack purchased successfully', [
                'entitlement' => [
                    'id'                => $entitlement->id,
                    'pack_slug'         => $entitlement->pack_slug,
                    'product_limit'     => $entitlement->product_limit,
                    'used_count'        => $entitlement->used_count,
                    'remaining_slots'   => $remaining,
                    'validity_ends_at'   => $entitlement->validity_ends_at->toISOString(),
                    'purchased_at'      => $entitlement->purchased_at->toISOString(),
                ],
            ], null, HttpResponse::HTTP_CREATED);
        } catch (RTDDomainException $e) {
            return Response::error($e->getMessage(), null, $e->getStatusCode());
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * GET current active entitlement for the authenticated converter.
     */
    public function entitlement(Request $request)
    {
        $entitlement = $this->packService->getActiveEntitlement($request->user()->id);
        if (!$entitlement) {
            return Response::success('No active entitlement', ['entitlement' => null]);
        }
        $remaining = max(0, $entitlement->product_limit - $entitlement->used_count);
        return Response::success('Active entitlement', [
            'entitlement' => [
                'id'                => $entitlement->id,
                'pack_slug'         => $entitlement->pack_slug,
                'product_limit'     => $entitlement->product_limit,
                'used_count'        => $entitlement->used_count,
                'remaining_slots'   => $remaining,
                'validity_ends_at'  => $entitlement->validity_ends_at->toISOString(),
                'purchased_at'      => $entitlement->purchased_at->toISOString(),
            ],
        ]);
    }
}
