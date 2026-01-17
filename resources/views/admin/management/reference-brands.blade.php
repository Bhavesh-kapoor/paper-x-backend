@extends('admin.layout')

@section('content')
<style>
    .brands-table-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08);
        background: #ffffff;
        overflow: hidden;
    }

    .brands-table-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 1.25rem 1.5rem;
        color: #ffffff;
    }

    .brands-table-card .card-header h5 {
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

    .btn-add {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: #ffffff;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: #ffffff;
    }

    .btn-edit {
        background: #17a2b8;
        border: none;
        color: #ffffff;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-edit:hover {
        background: #138496;
        color: #ffffff;
    }

    .btn-delete {
        background: #dc3545;
        border: none;
        color: #ffffff;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-delete:hover {
        background: #c82333;
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
        color: #212529;
        background: #ffffff;
    }

    .filter-group input::placeholder {
        color: #212529;
        opacity: 0.6;
    }

    .filter-group input:focus,
    .filter-group select:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        color: #212529;
        background: #ffffff;
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

    .modal-content {
        border-radius: 12px;
        border: none;
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
        border-bottom: none;
        border-radius: 12px 12px 0 0;
    }

    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }

    .modal-body .form-control {
        color: #212529;
        background: #ffffff;
        border: 2px solid #e9ecef;
    }

    .modal-body .form-control::placeholder {
        color: #212529;
        opacity: 0.6;
    }

    .modal-body .form-control:focus {
        border-color: #667eea;
        color: #212529;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .modal-body .form-label {
        color: #212529;
        font-weight: 600;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title">
            <i class="icon-base ti tabler-brand-apple me-2"></i>Reference Brands (Mill Brands)
        </h4>
        <div class="d-flex gap-2">
            <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                <i class="icon-base ti tabler-plus"></i>Add Brand
            </button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                <i class="icon-base ti tabler-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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
                        <input type="text" name="search" id="search" placeholder="Search by brand name..." value="{{ request('search') }}">
                    </div>
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
    <div class="card brands-table-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-brand-apple"></i>
                Brands List
            </h5>
        </div>
        <div class="results-info" id="resultsInfo">
            Showing {{ $brands->firstItem() ?? 0 }} to {{ $brands->lastItem() ?? 0 }} of {{ $brands->total() }} results
        </div>
        <div class="card-body position-relative" id="tableContainer">
            <div class="table-responsive">
                @include('admin.management.reference-brands-table', ['brands' => $brands])
            </div>
        </div>
    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="icon-base ti tabler-plus me-2"></i>Add New Brand
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addBrandForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div id="addBrandErrors" class="alert alert-danger" style="display: none;"></div>
                    <div class="mb-3">
                        <label class="form-label">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="add_brand_name" class="form-control" required style="color: #212529; background: #ffffff;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-add" id="addBrandBtn">
                        <i class="icon-base ti tabler-loader-2 d-none" id="addBrandSpinner" style="animation: spin 1s linear infinite;"></i>
                        <span id="addBrandBtnText">Create Brand</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="icon-base ti tabler-edit me-2"></i>Edit Brand
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBrandForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div id="editBrandErrors" class="alert alert-danger" style="display: none;"></div>
                    <div class="mb-3">
                        <label class="form-label">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_brand_name" class="form-control" required style="color: #212529; background: #ffffff;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-add" id="editBrandBtn">
                        <i class="icon-base ti tabler-loader-2 d-none" id="editBrandSpinner" style="animation: spin 1s linear infinite;"></i>
                        <span id="editBrandBtnText">Update Brand</span>
                    </button>
                </div>
            </form>
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

    tableContainer.innerHTML = '<div class="loading-overlay"><div class="spinner"></div></div>';

    fetch(`{{ route('admin.reference.brands') }}?${params.toString()}`, {
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
        attachEventListeners();
    })
    .catch(error => {
        console.error('Error:', error);
        location.reload();
    });
}

function attachEventListeners() {
    // Edit buttons
    document.querySelectorAll('.btn-edit-brand').forEach(btn => {
        btn.addEventListener('click', function() {
            const brandId = this.dataset.id;
            const brandName = this.dataset.name || '';
            
            document.getElementById('edit_brand_name').value = brandName;
            document.getElementById('editBrandForm').action = `{{ url('admin/reference/brands') }}/${brandId}`;
            
            new bootstrap.Modal(document.getElementById('editBrandModal')).show();
        });
    });

    // Delete buttons
    document.querySelectorAll('.btn-delete-brand').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this brand?')) {
                const brandId = this.dataset.id;
                deleteBrand(brandId);
            }
        });
    });
}

function deleteBrand(brandId) {
    const formData = new FormData();
    formData.append('_method', 'DELETE');
    
    fetch(`{{ url('admin/reference/brands') }}/${brandId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.json().catch(() => ({ success: true }));
        }
        throw new Error('Delete failed');
    })
    .then(data => {
        if (data.success !== false) {
            showAlert('Brand deleted successfully!', 'success');
            applyFilters();
        } else {
            showAlert(data.message || 'Error deleting brand', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Brand deleted successfully!', 'success');
        applyFilters();
    });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="icon-base ti tabler-${type === 'success' ? 'check' : 'alert-circle'} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-xxl');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
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
            attachEventListeners();
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = url;
        });
    }
});

// Add Brand Form AJAX
document.getElementById('addBrandForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('addBrandBtn');
    const spinner = document.getElementById('addBrandSpinner');
    const btnText = document.getElementById('addBrandBtnText');
    const errorDiv = document.getElementById('addBrandErrors');
    
    submitBtn.disabled = true;
    spinner.classList.remove('d-none');
    btnText.textContent = 'Creating...';
    errorDiv.style.display = 'none';
    errorDiv.innerHTML = '';
    
    fetch('{{ route("admin.reference.brands.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.json().catch(() => ({ success: true }));
        }
        return response.json().then(data => Promise.reject(data));
    })
    .then(data => {
        if (data.success !== false) {
            showAlert('Brand created successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addBrandModal')).hide();
            document.getElementById('addBrandForm').reset();
            applyFilters();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.errors) {
            let errorHtml = '<ul class="mb-0">';
            Object.values(error.errors).forEach(errors => {
                errors.forEach(err => {
                    errorHtml += `<li>${err}</li>`;
                });
            });
            errorHtml += '</ul>';
            errorDiv.innerHTML = errorHtml;
            errorDiv.style.display = 'block';
        } else {
            showAlert(error.message || 'Error creating brand', 'danger');
        }
    })
    .finally(() => {
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
        btnText.textContent = 'Create Brand';
    });
});

// Edit Brand Form AJAX
document.getElementById('editBrandForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('_method', 'PUT');
    const formAction = this.action;
    const submitBtn = document.getElementById('editBrandBtn');
    const spinner = document.getElementById('editBrandSpinner');
    const btnText = document.getElementById('editBrandBtnText');
    const errorDiv = document.getElementById('editBrandErrors');
    
    submitBtn.disabled = true;
    spinner.classList.remove('d-none');
    btnText.textContent = 'Updating...';
    errorDiv.style.display = 'none';
    errorDiv.innerHTML = '';
    
    fetch(formAction, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.json().catch(() => ({ success: true }));
        }
        return response.json().then(data => Promise.reject(data));
    })
    .then(data => {
        if (data.success !== false) {
            showAlert('Brand updated successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editBrandModal')).hide();
            applyFilters();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.errors) {
            let errorHtml = '<ul class="mb-0">';
            Object.values(error.errors).forEach(errors => {
                errors.forEach(err => {
                    errorHtml += `<li>${err}</li>`;
                });
            });
            errorHtml += '</ul>';
            errorDiv.innerHTML = errorHtml;
            errorDiv.style.display = 'block';
        } else {
            showAlert(error.message || 'Error updating brand', 'danger');
        }
    })
    .finally(() => {
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
        btnText.textContent = 'Update Brand';
    });
});

// Initialize
updateActiveFiltersCount();
attachEventListeners();
</script>
@endsection
