@php
    $kindMeta = [
        'credit_pack'      => ['label' => 'Credit Pack', 'class' => 'kind-credit-pack', 'icon' => 'tabler-package'],
        'direct_pay'       => ['label' => 'Direct Pay', 'class' => 'kind-direct-pay', 'icon' => 'tabler-bolt'],
        'rtd_platform_fee' => ['label' => 'RTD Platform Fee', 'class' => 'kind-rtd', 'icon' => 'tabler-truck-delivery'],
    ];
@endphp
<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th style="width: 60px;">S.No</th>
                <th>Invoice No</th>
                <th>User</th>
                <th>Type</th>
                <th class="text-end">Amount</th>
                <th class="text-end">GST</th>
                <th>Paid On</th>
                <th style="width: 120px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            @php
                $meta = $kindMeta[$invoice['kind']] ?? ['label' => ucfirst(str_replace('_', ' ', $invoice['kind'])), 'class' => 'kind-direct-pay', 'icon' => 'tabler-receipt'];
            @endphp
            <tr>
                <td>
                    <strong style="color: #667eea;">{{ $loop->index + $invoices->firstItem() }}</strong>
                </td>
                <td>
                    <span class="invoice-no-cell">{{ $invoice['invoice_no'] }}</span>
                    <div class="user-email">{{ $invoice['title'] }}</div>
                </td>
                <td>
                    <div class="user-details">
                        <div class="user-name">{{ $invoice['user']['name'] ?? 'N/A' }}</div>
                        <div class="user-email">{{ $invoice['user']['company_name'] ?? ($invoice['user']['mobile'] ?? '') }}</div>
                    </div>
                </td>
                <td>
                    <span class="kind-badge {{ $meta['class'] }}">
                        <i class="icon-base ti {{ $meta['icon'] }}"></i>
                        {{ $meta['label'] }}
                    </span>
                </td>
                <td class="text-end">
                    <strong style="color: #212529;">₹{{ number_format($invoice['total_inr'], 2) }}</strong>
                </td>
                <td class="text-end">
                    <span class="text-muted">{{ $invoice['gst_amount_inr'] > 0 ? '₹' . number_format($invoice['gst_amount_inr'], 2) : '—' }}</span>
                </td>
                <td>
                    <div class="info-item">
                        <i class="icon-base ti tabler-calendar"></i>
                        <span>{{ $invoice['paid_at'] ? \Carbon\Carbon::parse($invoice['paid_at'])->format('d M Y') : 'N/A' }}</span>
                    </div>
                </td>
                <td>
                    <a href="{{ route('admin.invoices.detail', $invoice['key']) }}" class="btn-view-details">
                        <i class="icon-base ti tabler-eye"></i>
                        View
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <i class="icon-base ti tabler-file-off" style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem;"></i>
                        <p class="text-muted mb-0">No invoices found</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-wrapper">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <p class="mb-0 text-muted" style="font-size: 0.9rem; font-weight: 500;">
                <i class="icon-base ti tabler-info-circle me-1"></i>
                Showing <strong>{{ $invoices->firstItem() ?? 0 }}</strong> to <strong>{{ $invoices->lastItem() ?? 0 }}</strong> of <strong>{{ $invoices->total() }}</strong> results
            </p>
        </div>
        <div>
            {{ $invoices->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
