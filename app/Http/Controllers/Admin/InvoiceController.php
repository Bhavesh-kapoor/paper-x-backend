<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\URL;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {
    }

    /**
     * Global list of all users' paid invoices, with filters + revenue summary.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'kind', 'date_from', 'date_to']);
        $page    = (int) $request->query('page', 1);

        $result  = $this->invoiceService->listAllForAdmin($filters, $page, 20);
        $summary = $result['summary'];

        $invoices = new LengthAwarePaginator(
            $result['data'],
            $result['meta']['total'],
            $result['meta']['per_page'],
            $result['meta']['current_page'],
            ['path' => route('admin.invoices')]
        );
        $invoices->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'html'    => view('admin.management.invoices-table', compact('invoices'))->render(),
                'total'   => $result['meta']['total'],
                'summary' => [
                    'total_revenue'     => '₹' . number_format($summary['total_revenue_inr'], 2),
                    'total_count'       => $summary['count'],
                    'credit_pack'       => '₹' . number_format($summary['by_kind']['credit_pack']['total_inr'], 2),
                    'credit_pack_count' => $summary['by_kind']['credit_pack']['count'],
                    'direct_pay'        => '₹' . number_format($summary['by_kind']['direct_pay']['total_inr'], 2),
                    'direct_pay_count'  => $summary['by_kind']['direct_pay']['count'],
                    'rtd'               => '₹' . number_format($summary['by_kind']['rtd_platform_fee']['total_inr'], 2),
                    'rtd_count'         => $summary['by_kind']['rtd_platform_fee']['count'],
                ],
            ]);
        }

        $kinds = [
            'credit_pack'      => 'Credit Pack',
            'direct_pay'       => 'Direct Pay',
            'rtd_platform_fee' => 'RTD Platform Fee',
        ];

        return view('admin.management.invoices', compact('invoices', 'summary', 'kinds'));
    }

    /**
     * Invoice detail + a signed PDF download URL (reuses the mobile download route).
     */
    public function show(Request $request, string $key)
    {
        $invoice = $this->invoiceService->findAny($key);

        $downloadUrl = URL::temporarySignedRoute(
            'invoices.download',
            now()->addMinutes(30),
            ['key' => $invoice['key'], 'user' => $invoice['user']['id']]
        );

        return view('admin.management.invoice-detail', compact('invoice', 'downloadUrl'));
    }
}
