<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {
    }

    public function index(Request $request)
    {
        try {
            $result = $this->invoiceService->listForUser(
                $request->user(),
                (int) $request->query('page', 1),
                (int) $request->query('per_page', 15)
            );

            return Response::success('Invoices retrieved successfully', $result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    public function show(Request $request, string $key)
    {
        try {
            $invoice = $this->invoiceService->findForUser($request->user(), $key);

            $invoice['download_url'] = URL::temporarySignedRoute(
                'invoices.download',
                now()->addMinutes(30),
                ['key' => $invoice['key'], 'user' => $request->user()->id]
            );

            return Response::success('Invoice retrieved successfully', $invoice);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return Response::error('Invoice not found', null, HttpResponse::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Signed-URL PDF download — no sanctum auth so the device browser can open it.
     * Access is protected by the temporary signature + explicit user binding.
     */
    public function download(Request $request, string $key)
    {
        abort_unless($request->hasValidSignature(), 403);

        $user = User::findOrFail((int) $request->query('user'));

        try {
            $invoice = $this->invoiceService->findForUser($user, $key);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            abort(404, 'Invoice not found');
        }

        $pdf = Pdf::loadView('invoices.invoice', ['invoice' => $invoice])
            ->setPaper('a4');

        return $pdf->download($invoice['invoice_no'] . '.pdf');
    }
}
