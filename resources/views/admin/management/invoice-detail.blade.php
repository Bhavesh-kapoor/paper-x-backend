@extends('admin.layout')

@section('content')
@php
    $kindLabels = [
        'credit_pack'      => 'Credit Pack',
        'direct_pay'       => 'Direct Pay',
        'rtd_platform_fee' => 'RTD Platform Fee',
    ];
    $kindLabel = $kindLabels[$invoice['kind']] ?? ucfirst(str_replace('_', ' ', $invoice['kind']));
    $billTo = $invoice['bill_to'] ?? [];
    $billLocation = trim(collect([$billTo['city'] ?? null, $billTo['state'] ?? null])->filter()->implode(', '));
@endphp
<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
    .page-title { font-size: 1.5rem; font-weight: 700; color: #212529; margin: 0; }
    .btn-back { background: #6c757d; border: none; color: #ffffff; padding: 0.6rem 1.25rem; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; }
    .btn-back:hover { background: #5a6268; transform: translateX(-3px); color: #ffffff; }

    .inv-card { border-radius: 16px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.08); background: #ffffff; margin-bottom: 1.5rem; overflow: hidden; }
    .inv-card-body { padding: 1.5rem; }
    .inv-section-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.6px; color: #6c757d; font-weight: 700; margin-bottom: 1rem; }

    .inv-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 1.75rem; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; }
    .inv-hero .inv-no { font-size: 1.4rem; font-weight: 800; font-family: 'Courier New', monospace; }
    .inv-hero .inv-title { opacity: 0.9; margin-top: 0.35rem; }
    .inv-hero .inv-date { opacity: 0.85; font-size: 0.85rem; margin-top: 0.25rem; }
    .paid-chip { background: #dcfce7; color: #15803d; padding: 0.4rem 0.9rem; border-radius: 20px; font-weight: 800; font-size: 0.75rem; letter-spacing: 1px; }

    .inv-row { display: flex; justify-content: space-between; align-items: center; padding: 0.55rem 0; border-bottom: 1px solid #f1f1f1; font-size: 0.92rem; }
    .inv-row:last-child { border-bottom: none; }
    .inv-row .label { color: #6c757d; }
    .inv-row .value { color: #212529; font-weight: 600; text-align: right; word-break: break-word; }
    .inv-total-row { display: flex; justify-content: space-between; align-items: center; padding-top: 0.9rem; margin-top: 0.4rem; border-top: 2px solid #e9ecef; }
    .inv-total-row .label { font-weight: 700; color: #212529; font-size: 1.05rem; }
    .inv-total-row .value { font-weight: 800; color: #667eea; font-size: 1.35rem; }

    .btn-download { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: #fff; padding: 0.85rem 1.75rem; border-radius: 10px; font-weight: 700; font-size: 0.95rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; }
    .btn-download:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(102,126,234,0.4); color: #fff; }

    .kind-badge-inline { background: rgba(255,255,255,0.2); color: #fff; padding: 0.3rem 0.75rem; border-radius: 8px; font-size: 0.75rem; font-weight: 600; display: inline-block; margin-top: 0.5rem; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="page-header">
        <h4 class="page-title">
            <i class="icon-base ti tabler-file-invoice me-2" style="color: #667eea;"></i>Invoice Details
        </h4>
        <a href="{{ route('admin.invoices') }}" class="btn-back">
            <i class="icon-base ti tabler-arrow-left"></i>Back to Invoices
        </a>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <!-- Hero -->
            <div class="inv-card">
                <div class="inv-hero">
                    <div>
                        <div class="inv-no">{{ $invoice['invoice_no'] }}</div>
                        <div class="inv-title">{{ $invoice['title'] }}</div>
                        <div class="inv-date">
                            {{ $invoice['paid_at'] ? \Carbon\Carbon::parse($invoice['paid_at'])->format('d M Y, h:i A') : '—' }}
                        </div>
                        <span class="kind-badge-inline">{{ $kindLabel }}</span>
                    </div>
                    <span class="paid-chip">PAID</span>
                </div>
            </div>

            <!-- Amount -->
            <div class="inv-card">
                <div class="inv-card-body">
                    <div class="inv-section-title">Amount Breakdown</div>
                    <div class="inv-row">
                        <span class="label">{{ $invoice['title'] }}</span>
                        <span class="value">₹{{ number_format($invoice['base_amount_inr'], 2) }}</span>
                    </div>
                    @if(!is_null($invoice['credits']))
                    <div class="inv-row">
                        <span class="label">Credits Added</span>
                        <span class="value">{{ number_format($invoice['credits']) }} Credits</span>
                    </div>
                    @endif
                    @if($invoice['gst_amount_inr'] > 0)
                    <div class="inv-row">
                        <span class="label">GST ({{ $invoice['gst_percent'] }}%)</span>
                        <span class="value">₹{{ number_format($invoice['gst_amount_inr'], 2) }}</span>
                    </div>
                    @endif
                    <div class="inv-total-row">
                        <span class="label">Total Paid</span>
                        <span class="value">₹{{ number_format($invoice['total_inr'], 2) }}</span>
                    </div>
                </div>
            </div>

            <a href="{{ $downloadUrl }}" target="_blank" rel="noopener" class="btn-download">
                <i class="icon-base ti tabler-download"></i>
                Download Invoice (PDF)
            </a>
        </div>

        <div class="col-lg-5">
            <!-- Payment details -->
            <div class="inv-card">
                <div class="inv-card-body">
                    <div class="inv-section-title">Payment Details</div>
                    <div class="inv-row">
                        <span class="label">Method</span>
                        <span class="value">Razorpay</span>
                    </div>
                    @if(!empty($invoice['razorpay_payment_id']))
                    <div class="inv-row">
                        <span class="label">Payment ID</span>
                        <span class="value">{{ $invoice['razorpay_payment_id'] }}</span>
                    </div>
                    @endif
                    @if(!empty($invoice['razorpay_order_id']))
                    <div class="inv-row">
                        <span class="label">Order ID</span>
                        <span class="value">{{ $invoice['razorpay_order_id'] }}</span>
                    </div>
                    @endif
                    <div class="inv-row">
                        <span class="label">Receipt</span>
                        <span class="value">{{ $invoice['receipt'] ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Billed to -->
            <div class="inv-card">
                <div class="inv-card-body">
                    <div class="inv-section-title">Billed To</div>
                    <div class="inv-row">
                        <span class="label">Name</span>
                        <span class="value">
                            @if(!empty($invoice['user']['id']))
                                <a href="{{ route('admin.users.detail', $invoice['user']['id']) }}" style="color:#667eea; font-weight:600;">
                                    {{ $billTo['name'] ?? ($invoice['user']['name'] ?? '—') }}
                                </a>
                            @else
                                {{ $billTo['name'] ?? '—' }}
                            @endif
                        </span>
                    </div>
                    @if(!empty($billTo['company_name']))
                    <div class="inv-row">
                        <span class="label">Company</span>
                        <span class="value">{{ $billTo['company_name'] }}</span>
                    </div>
                    @endif
                    @if(!empty($invoice['user']['mobile']))
                    <div class="inv-row">
                        <span class="label">Mobile</span>
                        <span class="value">{{ $invoice['user']['mobile'] }}</span>
                    </div>
                    @endif
                    @if(!empty($billTo['gstin']))
                    <div class="inv-row">
                        <span class="label">GSTIN</span>
                        <span class="value">{{ $billTo['gstin'] }}</span>
                    </div>
                    @endif
                    @if($billLocation)
                    <div class="inv-row">
                        <span class="label">Location</span>
                        <span class="value">{{ $billLocation }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
