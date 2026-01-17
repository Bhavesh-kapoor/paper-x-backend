@extends('admin.layout')

@section('content')
<style>
    .machine-dealers-table-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08);
        background: #ffffff;
        overflow: hidden;
    }

    .machine-dealers-table-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 1.25rem 1.5rem;
        color: #ffffff;
    }

    .machine-dealers-table-card .card-header h5 {
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

    .status-active {
        background: #d4edda;
        color: #155724;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }

    .status-complete {
        background: #d1ecf1;
        color: #0c5460;
    }

    .status-incomplete {
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

    .filter-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 1.5rem;
        border: 1px solid #e9ecef;
    }

    .filter-card-header {
        background: #f8f9fa;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .filter-card-header h6 {
        margin: 0;
        font-weight: 600;
        color: #212529;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-card-body {
        padding: 1.5rem;
    }

    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #212529;
        margin-bottom: 0.5rem;
    }

    .filter-group input,
    .filter-group select {
        border-radius: 8px;
        border: 1px solid #e9ecef;
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }

    .filter-group input:focus,
    .filter-group select:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-apply-filters {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: #ffffff;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-apply-filters:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-reset-filters {
        background: #6c757d;
        border: none;
        color: #ffffff;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-reset-filters:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .badge-count {
        background: #667eea;
        color: #ffffff;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 0.25rem;
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
        border: 3px solid #f3f3f3;
        border-top: 3px solid #667eea;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .results-info {
        padding: 1rem 1.5rem;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
    }

    .page-title {
        color: #212529;
        font-weight: 700;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title">
            <i class="icon-base ti tabler-tools me-2"></i>All Machine Dealers
        </h4>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
            <i class="icon-base ti tabler-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <!-- Filters Card -->
    <div class="filter-card">
        <div class="filter-card-header" onclick="toggleFilters()">
            <h6>
                <i class="icon-base ti tabler-filter"></i>
                Filters
                <span class="badge-count" id="activeFiltersCount">0</span>
            </h6>
            <i class="icon-base ti tabler-chevron-down" id="filterIcon"></i>
        </div>
        <div class="filter-card-body" id="filterBody" style="display: none;">
            <form id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" id="search" placeholder="Search by name, email, city..." value="{{ request('search') }}">
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Profile Complete</label>
                        <select name="profile_complete" id="profile_complete">
                            <option value="">All</option>
                            <option value="yes" {{ request('profile_complete') == 'yes' ? 'selected' : '' }}>Yes</option>
                            <option value="no" {{ request('profile_complete') == 'no' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>City</label>
                        <input type="text" name="city" id="city" list="cities" placeholder="Enter city" value="{{ request('city') }}">
                        <datalist id="cities">
                            @foreach($cities as $city)
                                <option value="{{ $city }}">
                            @endforeach
                        </datalist>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}">
                    </div>
                    <div class="filter-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}">
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-apply-filters">
                        <i class="icon-base ti tabler-filter me-1"></i>Apply Filters
                    </button>
                    <button type="button" class="btn-reset-filters" onclick="resetFilters()">
                        <i class="icon-base ti tabler-x me-1"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card machine-dealers-table-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-tools"></i>
                Machine Dealers List
            </h5>
        </div>
        <div class="results-info" id="resultsInfo">
            Showing {{ $machineDealers->firstItem() ?? 0 }} to {{ $machineDealers->lastItem() ?? 0 }} of {{ $machineDealers->total() }} results
        </div>
        <div class="card-body position-relative" id="tableContainer">
            <div class="table-responsive">
                @include('admin.management.machine-dealers-table', ['machineDealers' => $machineDealers])
            </div>
        </div>
    </div>
</div>

<script>
let filterTimeout;
const filterForm = document.getElementById('filterForm');
const tableContainer = document.getElementById('tableContainer');
const resultsInfo = document.getElementById('resultsInfo');

function toggleFilters() {
    const filterBody = document.getElementById('filterBody');
    const filterIcon = document.getElementById('filterIcon');
    if (filterBody.style.display === 'none') {
        filterBody.style.display = 'block';
        filterIcon.style.transform = 'rotate(180deg)';
    } else {
        filterBody.style.display = 'none';
        filterIcon.style.transform = 'rotate(0deg)';
    }
}

function updateActiveFiltersCount() {
    const formData = new FormData(filterForm);
    let count = 0;
    for (let [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            count++;
        }
    }
    document.getElementById('activeFiltersCount').textContent = count;
}

function resetFilters() {
    filterForm.reset();
    updateActiveFiltersCount();
    applyFilters();
}

function applyFilters() {
    const formData = new FormData(filterForm);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            params.append(key, value);
        }
    }

    // Show loading
    tableContainer.innerHTML = '<div class="loading-overlay"><div class="spinner"></div></div>';

    fetch(`{{ route('admin.machine-dealers') }}?${params.toString()}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        tableContainer.innerHTML = '<div class="table-responsive">' + data.html + '</div>';
        resultsInfo.textContent = `Showing ${data.firstItem || 0} to ${data.lastItem || 0} of ${data.total} results`;
        updateActiveFiltersCount();
    })
    .catch(error => {
        console.error('Error:', error);
        location.reload();
    });
}

filterForm.addEventListener('submit', function(e) {
    e.preventDefault();
    applyFilters();
});

// Pagination links
document.addEventListener('click', function(e) {
    if (e.target.closest('.pagination a')) {
        e.preventDefault();
        const url = e.target.closest('.pagination a').href;
        
        // Show loading
        tableContainer.innerHTML = '<div class="loading-overlay"><div class="spinner"></div></div>';
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            tableContainer.innerHTML = '<div class="table-responsive">' + data.html + '</div>';
            resultsInfo.textContent = `Showing ${data.firstItem || 0} to ${data.lastItem || 0} of ${data.total} results`;
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = url;
        });
    }
});

// Initialize
updateActiveFiltersCount();
</script>
@endsection
