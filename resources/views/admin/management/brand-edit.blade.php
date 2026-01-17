@extends('admin.layout')

@section('content')
<style>
    .edit-card {
        border-radius: 20px;
        border: none;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.08);
        background: #ffffff;
        overflow: hidden;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }

    .edit-card:hover {
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.2), 0 6px 16px rgba(0, 0, 0, 0.12);
    }

    .edit-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 1.5rem 2rem;
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }

    .edit-card .card-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse 3s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 0.6; }
    }

    .edit-card .card-header h5 {
        color: #ffffff;
        font-weight: 700;
        margin: 0;
        font-size: 1.25rem;
        position: relative;
        z-index: 1;
    }

    .form-section {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        border-left: 4px solid #667eea;
    }

    .form-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #495057;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e9ecef;
    }

    .form-section-title i {
        color: #667eea;
        font-size: 1.25rem;
    }

    .form-label {
        font-weight: 700;
        color: #495057;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
    }

    .form-label i {
        color: #667eea;
        font-size: 1rem;
    }

    .form-control, .form-select {
        border-radius: 12px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1.25rem;
        transition: all 0.3s ease;
        font-size: 0.95rem;
        background: #ffffff;
        color: #212529;
    }

    .form-control::placeholder {
        color: #6c757d;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        background: #ffffff;
        color: #212529;
        outline: none;
    }

    .form-control:hover, .form-select:hover {
        border-color: #667eea;
    }

    .form-select {
        background-color: #ffffff;
        color: #212529;
    }

    .form-select option {
        background: #ffffff;
        color: #212529;
        padding: 0.5rem;
    }

    .form-select[multiple] {
        min-height: 150px;
        padding: 0.75rem;
        background: #ffffff;
    }

    .form-select[multiple] option {
        padding: 0.5rem 1rem;
        margin: 0.25rem 0;
        border-radius: 8px;
        background: #ffffff;
        color: #212529;
    }

    .form-select[multiple] option:checked {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
    }

    .btn-submit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: #ffffff;
        padding: 0.75rem 2rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
        color: #ffffff;
    }

    .btn-submit:active {
        transform: translateY(-1px);
    }

    .btn-cancel {
        background: #6c757d;
        border: none;
        color: #ffffff;
        padding: 0.75rem 2rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-cancel:hover {
        background: #5a6268;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(108, 117, 125, 0.4);
        color: #ffffff;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 2rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 20px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .form-help-text {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-help-text i {
        color: #667eea;
    }

    .input-group-icon {
        position: relative;
    }

    .input-group-icon i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #667eea;
        z-index: 10;
    }

    .input-group-icon .form-control {
        padding-left: 2.75rem;
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border: 2px solid #b8dacc;
        border-radius: 12px;
        color: #155724;
        padding: 1rem 1.5rem;
        font-weight: 600;
    }

    .alert-danger {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border: 2px solid #f5c6cb;
        border-radius: 12px;
        color: #721c24;
        padding: 1rem 1.5rem;
        font-weight: 600;
    }

    .border-top {
        border-top: 2px solid #e9ecef !important;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="page-header">
        <div>
            <h4 class="page-title">
                <i class="icon-base ti tabler-edit"></i>
                Edit Brand
            </h4>
            <p class="text-muted mb-0 mt-2" style="font-size: 1rem; font-weight: 600;">
                <i class="icon-base ti tabler-id me-1" style="color: #667eea;"></i>Brand ID: #{{ $brand->id }}
            </p>
        </div>
        <a href="{{ route('admin.brands.detail', $brand->id) }}" class="btn-cancel">
            <i class="icon-base ti tabler-arrow-left"></i>Back to Details
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <i class="icon-base ti tabler-alert-circle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card edit-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-brand me-2"></i>Brand Information
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <form action="{{ route('admin.brands.update', $brand->id) }}" method="POST" id="brandEditForm">
                @csrf
                @method('PUT')

                <!-- Basic Information -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="icon-base ti tabler-info-circle"></i>
                        Basic Information
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-brand"></i>Name (Mill Brand)
                            </label>
                            <div class="input-group-icon">
                                <i class="icon-base ti tabler-brand"></i>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $brand->name ?? '') }}" placeholder="Enter brand name">
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-building"></i>Company Name
                            </label>
                            <div class="input-group-icon">
                                <i class="icon-base ti tabler-building"></i>
                                <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $brand->company_name ?? '') }}" placeholder="Enter company name">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-tag"></i>Brand Name
                            </label>
                            <div class="input-group-icon">
                                <i class="icon-base ti tabler-tag"></i>
                                <input type="text" name="brand_name" class="form-control" value="{{ old('brand_name', $brand->brand_name ?? '') }}" placeholder="Enter brand name">
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-user"></i>Contact Person Name
                            </label>
                            <div class="input-group-icon">
                                <i class="icon-base ti tabler-user"></i>
                                <input type="text" name="contact_person_name" class="form-control" value="{{ old('contact_person_name', $brand->contact_person_name ?? '') }}" placeholder="Enter contact person name">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="icon-base ti tabler-phone"></i>
                        Contact Information
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-phone"></i>Mobile
                            </label>
                            <div class="input-group-icon">
                                <i class="icon-base ti tabler-phone"></i>
                                <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $brand->mobile ?? '') }}" placeholder="Enter mobile number">
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-mail"></i>Email
                            </label>
                            <div class="input-group-icon">
                                <i class="icon-base ti tabler-mail"></i>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $brand->email ?? '') }}" placeholder="Enter email address">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-file-text"></i>GST Number
                            </label>
                            <div class="input-group-icon">
                                <i class="icon-base ti tabler-file-text"></i>
                                <input type="text" name="gst" class="form-control" value="{{ old('gst', $brand->gst ?? '') }}" placeholder="Enter GST number">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location Information -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="icon-base ti tabler-map-pin"></i>
                        Location Information
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-map-pin"></i>City
                            </label>
                            <div class="input-group-icon">
                                <i class="icon-base ti tabler-map-pin"></i>
                                <input type="text" name="city" class="form-control" value="{{ old('city', $brand->city ?? '') }}" placeholder="Enter city">
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-map"></i>Location
                            </label>
                            <div class="input-group-icon">
                                <i class="icon-base ti tabler-map"></i>
                                <input type="text" name="location" class="form-control" value="{{ old('location', $brand->location ?? '') }}" placeholder="Enter location">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-map-pin"></i>Latitude
                            </label>
                            <div class="input-group-icon">
                                <i class="icon-base ti tabler-map-pin"></i>
                                <input type="number" step="any" name="latitude" class="form-control" value="{{ old('latitude', $brand->latitude ?? '') }}" placeholder="Enter latitude">
                            </div>
                            <div class="form-help-text">
                                <i class="icon-base ti tabler-info-circle"></i>
                                Range: -90 to 90
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-map-pin"></i>Longitude
                            </label>
                            <div class="input-group-icon">
                                <i class="icon-base ti tabler-map-pin"></i>
                                <input type="number" step="any" name="longitude" class="form-control" value="{{ old('longitude', $brand->longitude ?? '') }}" placeholder="Enter longitude">
                            </div>
                            <div class="form-help-text">
                                <i class="icon-base ti tabler-info-circle"></i>
                                Range: -180 to 180
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status & Settings -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="icon-base ti tabler-settings"></i>
                        Status & Settings
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-toggle-left"></i>Status
                            </label>
                            <select name="status" class="form-select">
                                <option value="PENDING" {{ old('status', $brand->status->value ?? 'PENDING') == 'PENDING' ? 'selected' : '' }}>Pending</option>
                                <option value="ACTIVE" {{ old('status', $brand->status->value ?? 'PENDING') == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                                <option value="INACTIVE" {{ old('status', $brand->status->value ?? 'PENDING') == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="icon-base ti tabler-checklist"></i>Profile Complete
                            </label>
                            <select name="profile_complete" class="form-select">
                                <option value="0" {{ old('profile_complete', $brand->profile_complete ? 1 : 0) == 0 ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('profile_complete', $brand->profile_complete ? 1 : 0) == 1 ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Brand Types -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="icon-base ti tabler-tags"></i>
                        Brand Types
                    </div>
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="icon-base ti tabler-tag"></i>Select Brand Types
                        </label>
                        <select name="brand_type_ids[]" class="form-select" multiple size="8">
                            @foreach($brandTypes as $brandType)
                                <option value="{{ $brandType->id }}" {{ (collect($brand->brandTypes->pluck('id'))->contains($brandType->id)) ? 'selected' : '' }}>
                                    {{ $brandType->name }}
                                    @if($brandType->category)
                                        ({{ $brandType->category }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-help-text">
                            <i class="icon-base ti tabler-info-circle"></i>
                            Hold <strong>Ctrl</strong> (Windows) or <strong>Cmd</strong> (Mac) to select multiple brand types
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex gap-3 justify-content-end pt-3 border-top">
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="icon-base ti tabler-check"></i>Update Brand
                    </button>
                    <a href="{{ route('admin.brands.detail', $brand->id) }}" class="btn-cancel">
                        <i class="icon-base ti tabler-x"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('brandEditForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function(e) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="icon-base ti tabler-loader-2" style="animation: spin 1s linear infinite;"></i> Updating...';
    });

    // Add smooth focus effects
    const inputs = document.querySelectorAll('.form-control, .form-select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
});
</script>
@endsection
