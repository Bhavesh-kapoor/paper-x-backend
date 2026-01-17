@extends('admin.layout')

@section('content')
<style>
    .dealers-table-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08);
        background: #ffffff;
        overflow: hidden;
    }

    .dealers-table-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 1.25rem 1.5rem;
        color: #ffffff;
    }

    .dealers-table-card .card-header h5 {
        color: #ffffff;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .table {
        margin-bottom: 0;
    }

    .table thead th {
        background: #f8f9fa;
        color: #212529;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.75rem 0.5rem;
        border-bottom: 2px solid #e9ecef;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 0.75rem 0.5rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
        color: #495057;
        font-size: 0.85rem;
    }

    .table tbody tr {
        transition: all 0.2s ease;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
        transform: scale(1.01);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.9rem;
        margin-right: 0.75rem;
    }

    .user-info {
        display: flex;
        align-items: center;
    }

    .user-details {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-weight: 600;
        color: #212529;
        margin-bottom: 0.15rem;
    }

    .user-email {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .status-badge {
        padding: 0.35rem 0.7rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .status-verified {
        background: #d4edda;
        color: #155724;
    }

    .status-unverified {
        background: #fff3cd;
        color: #856404;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #495057;
        font-size: 0.9rem;
    }

    .info-item i {
        color: #6c757d;
        font-size: 0.9rem;
    }

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

    .btn-view-details:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: #ffffff;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #212529;
        margin: 0;
    }

    .btn-back {
        background: #6c757d;
        border: none;
        color: #ffffff;
        padding: 0.6rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-back:hover {
        background: #5a6268;
        transform: translateX(-3px);
        color: #ffffff;
    }

    .pagination-wrapper {
        padding: 1.25rem 1.5rem;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }

    .pagination {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .pagination .page-link {
        padding: 0.75rem 1rem;
        font-size: 1rem;
        font-weight: 600;
        color: #667eea;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        height: 44px;
    }

    .pagination .page-link:hover {
        background: #667eea;
        color: #ffffff;
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .pagination .page-item.disabled .page-link {
        color: #adb5bd;
        background: #f8f9fa;
        border-color: #e9ecef;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .pagination .page-item.disabled .page-link:hover {
        transform: none;
        box-shadow: none;
    }

    .pagination .page-link i {
        font-size: 1.4rem;
        font-weight: 700;
        line-height: 1;
    }

    .filters-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        background: #ffffff;
        margin-bottom: 1.5rem;
    }

    .filters-header {
        background: #f8f9fa;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        border-radius: 16px 16px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .filters-body {
        padding: 1.5rem;
    }

    .filter-group {
        margin-bottom: 1rem;
    }

    .filter-group label {
        font-weight: 600;
        color: #212529;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    .filter-input {
        width: 100%;
        padding: 0.6rem 1rem;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .filter-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-filter {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: #ffffff;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: #ffffff;
    }

    .btn-clear {
        background: #6c757d;
        border: none;
        color: #ffffff;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-clear:hover {
        background: #5a6268;
        color: #ffffff;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        border-radius: 16px;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .table-container {
        position: relative;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="page-header">
        <h4 class="page-title">
            <i class="icon-base ti tabler-users me-2" style="color: #667eea;"></i>All Dealers
        </h4>
        <a href="{{ route('admin.dashboard') }}" class="btn-back">
            <i class="icon-base ti tabler-arrow-left"></i>Back to Dashboard
        </a>
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
                <form method="GET" action="{{ route('admin.dealers') }}" id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <div class="filter-group">
                            <label><i class="icon-base ti tabler-search me-1"></i>Search</label>
                            <input type="text" name="search" id="search" class="filter-input" placeholder="Name, Email, Mobile, Company..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="filter-group">
                            <label><i class="icon-base ti tabler-user me-1"></i>Status</label>
                            <select name="status" id="status" class="filter-input">
                                <option value="">All Status</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="filter-group">
                            <label><i class="icon-base ti tabler-check me-1"></i>Profile</label>
                            <select name="profile_complete" id="profile_complete" class="filter-input">
                                <option value="">All</option>
                                <option value="yes" {{ request('profile_complete') == 'yes' ? 'selected' : '' }}>Complete</option>
                                <option value="no" {{ request('profile_complete') == 'no' ? 'selected' : '' }}>Incomplete</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="filter-group">
                            <label><i class="icon-base ti tabler-map-pin me-1"></i>City</label>
                            <input type="text" name="city" id="city" class="filter-input" placeholder="Enter city..." value="{{ request('city') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-group">
                            <label><i class="icon-base ti tabler-calendar me-1"></i>Date Range</label>
                            <div class="d-flex gap-2">
                                <input type="date" name="date_from" id="date_from" class="filter-input" value="{{ request('date_from') }}" placeholder="From">
                                <input type="date" name="date_to" id="date_to" class="filter-input" value="{{ request('date_to') }}" placeholder="To">
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

    <div class="card dealers-table-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-users"></i>
                Dealer Management
                <span class="badge bg-light text-dark ms-2">{{ $dealers->total() }} Total</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-container" id="dealersTableContainer">
                <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                    <div class="spinner"></div>
                </div>
                @include('admin.management.dealers-table', ['dealers' => $dealers])
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const dealersTableContainer = document.getElementById('dealersTableContainer');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');

    // Debounce function for search input
    let searchTimeout;
    const searchInput = document.getElementById('search');
    
    // Auto-submit on filter change (with debounce for search)
    const filterInputs = ['status', 'profile_complete', 'city', 'date_from', 'date_to'];
    
    filterInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('change', function() {
                loadDealers();
            });
        }
    });

    // Debounced search
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                loadDealers();
            }, 500);
        });
    }

    // Form submit handler
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        loadDealers();
    });

    // Clear filters
    clearFiltersBtn.addEventListener('click', function() {
        filterForm.reset();
        // Reset URL without page refresh
        window.history.pushState({}, '', '{{ route("admin.dealers") }}');
        loadDealers();
    });

    // Pagination links handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.pagination a')) {
            e.preventDefault();
            const url = e.target.closest('.pagination a').href;
            loadDealers(url);
        }
    });

    // Load dealers via AJAX
    function loadDealers(url = null) {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        
        // Add form data to params
        for (const [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value);
            }
        }

        // If URL is provided (for pagination), use it and merge params
        if (url) {
            const urlObj = new URL(url);
            urlObj.searchParams.forEach((value, key) => {
                params.set(key, value);
            });
            url = url.split('?')[0];
        } else {
            url = '{{ route("admin.dealers") }}';
        }

        // Show loading
        loadingOverlay.style.display = 'flex';
        applyFiltersBtn.disabled = true;
        applyFiltersBtn.innerHTML = '<i class="icon-base ti tabler-loader-2 me-1" style="animation: spin 1s linear infinite;"></i>Loading...';

        // Make AJAX request
        fetch(url + '?' + params.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            // Update table container
            dealersTableContainer.innerHTML = data.html;
            
            // Update total count in header
            const totalBadge = document.querySelector('.dealers-table-card .card-header .badge');
            if (totalBadge) {
                totalBadge.textContent = data.total + ' Total';
            }

            // Update URL without page refresh
            const newUrl = url + '?' + params.toString();
            window.history.pushState({}, '', newUrl);

            // Re-attach pagination handlers
            attachPaginationHandlers();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading dealers. Please try again.');
        })
        .finally(() => {
            // Hide loading
            loadingOverlay.style.display = 'none';
            applyFiltersBtn.disabled = false;
            applyFiltersBtn.innerHTML = '<i class="icon-base ti tabler-filter me-1"></i>Apply Filters';
        });
    }

    // Attach pagination handlers to new pagination links
    function attachPaginationHandlers() {
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadDealers(this.href);
            });
        });
    }

    // Initial attachment
    attachPaginationHandlers();
});
</script>
@endsection
