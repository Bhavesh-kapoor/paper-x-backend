@extends('admin.layout')

@section('content')
<style>
    .users-table-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08);
        background: #ffffff;
        overflow: hidden;
    }

    .users-table-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 1.25rem 1.5rem;
        color: #ffffff;
    }

    .users-table-card .card-header h5 {
        color: #ffffff;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .table { margin-bottom: 0; }

    .table thead th {
        background: #f8f9fa;
        color: #212529;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem;
        border-bottom: 2px solid #e9ecef;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
        color: #495057;
        font-size: 0.9rem;
    }

    .table tbody tr { transition: all 0.2s ease; }

    .table tbody tr:hover {
        background: #f8f9fa;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .invoice-no-cell {
        font-weight: 700;
        color: #212529;
        font-family: 'Courier New', monospace;
    }

    .user-details { display: flex; flex-direction: column; }
    .user-name { font-weight: 600; color: #212529; margin-bottom: 0.15rem; }
    .user-email { font-size: 0.8rem; color: #6c757d; }

    .kind-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        white-space: nowrap;
    }
    .kind-credit-pack { background: #e0e7ff; color: #4338ca; }
    .kind-direct-pay  { background: #dcfce7; color: #15803d; }
    .kind-rtd         { background: #fef3c7; color: #b45309; }

    .info-item { display: flex; align-items: center; gap: 0.5rem; color: #495057; font-size: 0.9rem; }
    .info-item i { color: #6c757d; font-size: 0.9rem; }

    .btn-view-details {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: #ffffff;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .btn-view-details:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); color: #ffffff; }

    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .page-title { font-size: 1.5rem; font-weight: 700; color: #212529; margin: 0; }

    .btn-back {
        background: #6c757d; border: none; color: #ffffff;
        padding: 0.6rem 1.25rem; border-radius: 8px; font-weight: 600;
        transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;
    }
    .btn-back:hover { background: #5a6268; transform: translateX(-3px); color: #ffffff; }

    /* Summary cards */
    .summary-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    @media (max-width: 992px) { .summary-row { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px) { .summary-row { grid-template-columns: 1fr; } }

    .summary-card {
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        color: #ffffff;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    .summary-card .summary-icon { position: absolute; right: 1rem; top: 1rem; font-size: 2.2rem; opacity: 0.25; }
    .summary-card .summary-label { font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
    .summary-card .summary-value { font-size: 1.6rem; font-weight: 800; margin-top: 0.35rem; }
    .summary-card .summary-sub { font-size: 0.8rem; opacity: 0.9; margin-top: 0.25rem; }

    .sc-total  { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .sc-pack   { background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%); }
    .sc-direct { background: linear-gradient(135deg, #15803d 0%, #22c55e 100%); }
    .sc-rtd    { background: linear-gradient(135deg, #b45309 0%, #f59e0b 100%); }

    .pagination-wrapper { padding: 1.25rem 1.5rem; background: #f8f9fa; border-top: 1px solid #e9ecef; }
    .pagination { margin: 0; }

    .filters-card { border-radius: 16px; border: none; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); background: #ffffff; margin-bottom: 1.5rem; }
    .filters-header { background: #f8f9fa; padding: 1rem 1.5rem; border-bottom: 1px solid #e9ecef; border-radius: 16px 16px 0 0; display: flex; align-items: center; justify-content: space-between; }
    .filters-body { padding: 1.5rem; }
    .filter-group { margin-bottom: 1rem; }
    .filter-group label { font-weight: 600; color: #212529; font-size: 0.85rem; margin-bottom: 0.5rem; display: block; }
    .filter-input { width: 100%; padding: 0.6rem 1rem; border: 1px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; transition: all 0.3s ease; }
    .filter-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }

    .btn-filter { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: #ffffff; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; }
    .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); color: #ffffff; }
    .btn-clear { background: #6c757d; border: none; color: #ffffff; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; }
    .btn-clear:hover { background: #5a6268; color: #ffffff; }

    .loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.9); display: flex; align-items: center; justify-content: center; z-index: 1000; border-radius: 16px; }
    .spinner { width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .table-container { position: relative; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="page-header">
        <h4 class="page-title">
            <i class="icon-base ti tabler-file-invoice me-2" style="color: #667eea;"></i>Invoices
        </h4>
        <a href="{{ route('admin.dashboard') }}" class="btn-back">
            <i class="icon-base ti tabler-arrow-left"></i>Back to Dashboard
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="summary-row">
        <div class="summary-card sc-total">
            <i class="icon-base ti tabler-cash summary-icon"></i>
            <div class="summary-label">Total Revenue</div>
            <div class="summary-value" id="sumTotalRevenue">₹{{ number_format($summary['total_revenue_inr'], 2) }}</div>
            <div class="summary-sub"><span id="sumTotalCount">{{ $summary['count'] }}</span> invoices</div>
        </div>
        <div class="summary-card sc-pack">
            <i class="icon-base ti tabler-package summary-icon"></i>
            <div class="summary-label">Credit Packs</div>
            <div class="summary-value" id="sumCreditPack">₹{{ number_format($summary['by_kind']['credit_pack']['total_inr'], 2) }}</div>
            <div class="summary-sub"><span id="sumCreditPackCount">{{ $summary['by_kind']['credit_pack']['count'] }}</span> invoices</div>
        </div>
        <div class="summary-card sc-direct">
            <i class="icon-base ti tabler-bolt summary-icon"></i>
            <div class="summary-label">Direct Pay</div>
            <div class="summary-value" id="sumDirectPay">₹{{ number_format($summary['by_kind']['direct_pay']['total_inr'], 2) }}</div>
            <div class="summary-sub"><span id="sumDirectPayCount">{{ $summary['by_kind']['direct_pay']['count'] }}</span> invoices</div>
        </div>
        <div class="summary-card sc-rtd">
            <i class="icon-base ti tabler-truck-delivery summary-icon"></i>
            <div class="summary-label">RTD Platform Fees</div>
            <div class="summary-value" id="sumRtd">₹{{ number_format($summary['by_kind']['rtd_platform_fee']['total_inr'], 2) }}</div>
            <div class="summary-sub"><span id="sumRtdCount">{{ $summary['by_kind']['rtd_platform_fee']['count'] }}</span> invoices</div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card filters-card">
        <div class="filters-header">
            <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                <i class="icon-base ti tabler-filter me-2" style="color: #667eea;"></i>Filters
            </h6>
            <button type="button" class="btn btn-sm btn-link text-decoration-none" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" style="color: #667eea;">
                <i class="icon-base ti tabler-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="filtersCollapse">
            <div class="filters-body">
                <form method="GET" action="{{ route('admin.invoices') }}" id="filterForm" class="row g-3">
                    <div class="col-md-4">
                        <div class="filter-group">
                            <label><i class="icon-base ti tabler-search me-1"></i>Search User</label>
                            <input type="text" name="search" id="search" class="filter-input" placeholder="Name, Email, Mobile, Company..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-group">
                            <label><i class="icon-base ti tabler-category me-1"></i>Type</label>
                            <select name="kind" id="kind" class="filter-input">
                                <option value="">All Types</option>
                                @foreach($kinds as $value => $label)
                                    <option value="{{ $value }}" {{ request('kind') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="filter-group">
                            <label><i class="icon-base ti tabler-calendar me-1"></i>Paid Date Range</label>
                            <div class="d-flex gap-2">
                                <input type="date" name="date_from" id="date_from" class="filter-input" value="{{ request('date_from') }}">
                                <input type="date" name="date_to" id="date_to" class="filter-input" value="{{ request('date_to') }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-filter" id="applyFiltersBtn">
                                <i class="icon-base ti tabler-filter me-1"></i>Apply Filters
                            </button>
                            <button type="button" class="btn-clear" id="clearFiltersBtn">
                                <i class="icon-base ti tabler-x me-1"></i>Clear
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card users-table-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-file-invoice"></i>
                All Invoices
                <span class="badge bg-light text-dark ms-2">{{ $invoices->total() }} Total</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-container" id="invoicesTableContainer">
                <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                    <div class="spinner"></div>
                </div>
                @include('admin.management.invoices-table', ['invoices' => $invoices])
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const tableContainer = document.getElementById('invoicesTableContainer');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');

    let searchTimeout;
    const searchInput = document.getElementById('search');
    const filterInputs = ['kind', 'date_from', 'date_to'];

    filterInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('change', function() { loadInvoices(); });
        }
    });

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() { loadInvoices(); }, 500);
        });
    }

    filterForm.addEventListener('submit', function(e) { e.preventDefault(); loadInvoices(); });

    clearFiltersBtn.addEventListener('click', function() {
        filterForm.reset();
        window.history.pushState({}, '', '{{ route("admin.invoices") }}');
        loadInvoices();
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.pagination a')) {
            e.preventDefault();
            loadInvoices(e.target.closest('.pagination a').href);
        }
    });

    function updateSummary(summary) {
        if (!summary) return;
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        set('sumTotalRevenue', summary.total_revenue);
        set('sumTotalCount', summary.total_count);
        set('sumCreditPack', summary.credit_pack);
        set('sumCreditPackCount', summary.credit_pack_count);
        set('sumDirectPay', summary.direct_pay);
        set('sumDirectPayCount', summary.direct_pay_count);
        set('sumRtd', summary.rtd);
        set('sumRtdCount', summary.rtd_count);
    }

    function loadInvoices(url = null) {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            if (value) { params.append(key, value); }
        }

        if (url) {
            const urlObj = new URL(url);
            urlObj.searchParams.forEach((value, key) => { params.set(key, value); });
            url = url.split('?')[0];
        } else {
            url = '{{ route("admin.invoices") }}';
        }

        loadingOverlay.style.display = 'flex';
        applyFiltersBtn.disabled = true;
        applyFiltersBtn.innerHTML = '<i class="icon-base ti tabler-loader-2 me-1" style="animation: spin 1s linear infinite;"></i>Loading...';

        fetch(url + '?' + params.toString(), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            tableContainer.innerHTML = data.html;
            const totalBadge = document.querySelector('.users-table-card .card-header .badge');
            if (totalBadge) { totalBadge.textContent = data.total + ' Total'; }
            updateSummary(data.summary);
            window.history.pushState({}, '', url + '?' + params.toString());
            attachPaginationHandlers();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading invoices. Please try again.');
        })
        .finally(() => {
            loadingOverlay.style.display = 'none';
            applyFiltersBtn.disabled = false;
            applyFiltersBtn.innerHTML = '<i class="icon-base ti tabler-filter me-1"></i>Apply Filters';
        });
    }

    function attachPaginationHandlers() {
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function(e) { e.preventDefault(); loadInvoices(this.href); });
        });
    }

    attachPaginationHandlers();
});
</script>
@endsection
