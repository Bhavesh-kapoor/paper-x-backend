<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\SubmitQuoteRequest;
use App\Services\QuotationService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationService $quotationService
    ) {
    }

    public function submitQuote(int $inquiryId, SubmitQuoteRequest $request)
    {
        try {
            $user = $request->user();
            $quotation = $this->quotationService->submitQuote($inquiryId, $user->id, $request->validated());

            return Response::success('quotation.submitted', $quotation, null, HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}





