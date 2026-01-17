@extends('admin.layout')

@section('content')
<style>
    .detail-card {
        border-radius: 20px;
        border: none;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.08);
        background: #ffffff;
        overflow: hidden;
        margin-bottom: 1.5rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .detail-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }

    .detail-card:hover {
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.2), 0 6px 16px rgba(0, 0, 0, 0.12);
        transform: translateY(-4px);
    }

    .detail-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 1.5rem 2rem;
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }

    .detail-card .card-header::before {
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

    .detail-card .card-header h5 {
        color: #ffffff;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.1rem;
        position: relative;
        z-index: 1;
    }

    .info-row {
        padding: 1rem 0;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.3s ease;
        position: relative;
    }

    .info-row::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .info-row:hover {
        background: linear-gradient(90deg, #f8f9fa 0%, #ffffff 100%);
        padding-left: 0.5rem;
    }

    .info-row:hover::before {
        opacity: 1;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 700;
        color: #495057;
        font-size: 0.95rem;
        width: 40%;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-label i {
        color: #667eea;
        font-size: 1rem;
    }

    .info-value {
        color: #212529;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .badge-active {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
        border: 1px solid #b8dacc;
    }

    .badge-pending {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .badge-inactive {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .badge-complete {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
        border: 1px solid #b8dacc;
    }

    .badge-incomplete {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        color: #856404;
        border: 1px solid #ffeaa7;
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

    .btn-back {
        background: #6c757d;
        border: none;
        color: #ffffff;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 700;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
    }

    .btn-back:hover {
        background: #5a6268;
        transform: translateX(-5px);
        box-shadow: 0 6px 16px rgba(108, 117, 125, 0.4);
        color: #ffffff;
    }

    .btn-action {
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 700;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-edit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
    }

    .btn-edit:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
        color: #ffffff;
    }

    .btn-delete {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: #ffffff;
    }

    .btn-delete:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.5);
        color: #ffffff;
    }

    .stat-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        border: 2px solid #e9ecef;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }

    .stat-card::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.05) 0%, transparent 70%);
        transition: all 0.4s ease;
    }

    .stat-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.25);
        border-color: #667eea;
    }

    .stat-card:hover::after {
        transform: scale(1.2);
    }

    .stat-value {
        font-size: 3rem;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.75rem;
        line-height: 1;
    }

    .stat-label {
        font-size: 1rem;
        color: #6c757d;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-icon {
        font-size: 2rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .table-modern {
        margin-bottom: 0;
    }

    .table-modern thead th {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #495057;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 1rem;
        border-bottom: 3px solid #e9ecef;
    }

    .table-modern tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
        color: #495057;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .table-modern tbody tr {
        transition: all 0.3s ease;
    }

    .table-modern tbody tr:hover {
        background: linear-gradient(90deg, #f8f9fa 0%, #ffffff 100%);
        transform: scale(1.01);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .user-avatar-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-weight: 800;
        font-size: 3rem;
        margin: 0 auto 2rem;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        border: 5px solid #ffffff;
        position: relative;
        transition: all 0.3s ease;
    }

    .user-avatar-large::after {
        content: '';
        position: absolute;
        inset: -5px;
        border-radius: 50%;
        padding: 5px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .user-avatar-large:hover {
        transform: scale(1.1) rotate(5deg);
    }

    .user-avatar-large:hover::after {
        opacity: 1;
    }

    .badge-type {
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .badge-type:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }

    .empty-state {
        text-align: center;
        padding: 4rem 1rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 5rem;
        background: linear-gradient(135deg, #dee2e6 0%, #ced4da 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 1.5rem;
    }

    .info-section-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e9ecef;
    }

    .info-section-header i {
        font-size: 1.5rem;
        color: #667eea;
    }

    .info-section-header h6 {
        margin: 0;
        font-weight: 700;
        color: #495057;
        font-size: 1.1rem;
    }

    .link-primary {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .link-primary:hover {
        color: #764ba2;
        text-decoration: underline;
        transform: translateX(3px);
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="page-header">
        <div>
            <h4 class="page-title">
                <i class="icon-base ti tabler-brand"></i>
                Brand Details
            </h4>
            <p class="text-muted mb-0 mt-2" style="font-size: 1rem; font-weight: 600;">
                <i class="icon-base ti tabler-id me-1" style="color: #667eea;"></i>Brand ID: #{{ $brand->id }}
                @if($brand->user_id)
                    | <i class="icon-base ti tabler-user me-1 ms-3" style="color: #667eea;"></i>User ID: #{{ $brand->user_id }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.brands.edit', $brand->id) }}" class="btn-action btn-edit">
                <i class="icon-base ti tabler-edit"></i>Edit Brand
            </a>
            <form action="{{ route('admin.brands.delete', $brand->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this brand? This action cannot be undone.');" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-action btn-delete">
                    <i class="icon-base ti tabler-trash"></i>Delete
                </button>
            </form>
            <a href="{{ route('admin.brands') }}" class="btn-back">
                <i class="icon-base ti tabler-arrow-left"></i>Back to Brands
            </a>
        </div>
    </div>

    <!-- Enhanced Overview Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value">{{ $totalInquiries }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-file-text stat-icon"></i>Total Inquiries
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value">{{ $activeInquiries }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-file-check stat-icon"></i>Active Inquiries
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value">{{ $completedInquiries }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-check stat-icon"></i>Completed Inquiries
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Brand Information -->
        <div class="col-md-6 mb-4">
            <div class="card detail-card">
                <div class="card-header">
                    <h5>
                        <i class="icon-base ti tabler-brand"></i>
                        Brand Information
                    </h5>
                </div>
                <div class="card-body" style="padding: 2rem;">
                    <div class="user-avatar-large">
                        {{ strtoupper(substr($brand->name ?? $brand->company_name ?? $brand->brand_name ?? 'B', 0, 1)) }}
                    </div>
                    <table class="table table-borderless">
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-id"></i>Brand ID:
                            </td>
                            <td class="info-value"><strong style="color: #667eea;">#{{ $brand->id }}</strong></td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-tag"></i>Type:
                            </td>
                            <td class="info-value">
                                @if($brand->name && !$brand->user_id)
                                    <span class="badge bg-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700;">Mill Brand</span>
                                @elseif($brand->user_id)
                                    <span class="badge bg-info" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700;">User Brand</span>
                                @else
                                    <span class="badge bg-secondary" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700;">Unknown</span>
                                @endif
                            </td>
                        </tr>
                        @if($brand->name)
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-brand"></i>Name:
                            </td>
                            <td class="info-value"><strong style="font-size: 1.1rem;">{{ $brand->name }}</strong></td>
                        </tr>
                        @endif
                        @if($brand->company_name)
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-building"></i>Company Name:
                            </td>
                            <td class="info-value"><strong style="font-size: 1.1rem;">{{ $brand->company_name }}</strong></td>
                        </tr>
                        @endif
                        @if($brand->brand_name)
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-tag"></i>Brand Name:
                            </td>
                            <td class="info-value">{{ $brand->brand_name }}</td>
                        </tr>
                        @endif
                        @if($brand->contact_person_name)
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-user"></i>Contact Person:
                            </td>
                            <td class="info-value">{{ $brand->contact_person_name }}</td>
                        </tr>
                        @endif
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-toggle-left"></i>Status:
                            </td>
                            <td class="info-value">
                                @if($brand->status->value === 'ACTIVE')
                                <span class="status-badge badge-active">
                                    <i class="icon-base ti tabler-check"></i>Active
                                </span>
                                @elseif($brand->status->value === 'PENDING')
                                <span class="status-badge badge-pending">
                                    <i class="icon-base ti tabler-clock"></i>Pending
                                </span>
                                @else
                                <span class="status-badge badge-inactive">
                                    <i class="icon-base ti tabler-x"></i>Inactive
                                </span>
                                @endif
                            </td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-checklist"></i>Profile Complete:
                            </td>
                            <td class="info-value">
                                @if($brand->profile_complete)
                                <span class="status-badge badge-complete">
                                    <i class="icon-base ti tabler-check"></i>Yes
                                </span>
                                @else
                                <span class="status-badge badge-incomplete">
                                    <i class="icon-base ti tabler-alert-circle"></i>No
                                </span>
                                @endif
                            </td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-calendar"></i>Created At:
                            </td>
                            <td class="info-value">{{ $brand->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-clock"></i>Updated At:
                            </td>
                            <td class="info-value">{{ $brand->updated_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Contact & Location Information -->
        <div class="col-md-6 mb-4">
            <div class="card detail-card">
                <div class="card-header">
                    <h5>
                        <i class="icon-base ti tabler-phone"></i>
                        Contact & Location
                    </h5>
                </div>
                <div class="card-body" style="padding: 2rem;">
                    <table class="table table-borderless">
                        @if($brand->mobile)
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-phone"></i>Mobile:
                            </td>
                            <td class="info-value">
                                <a href="tel:{{ $brand->mobile }}" class="link-primary">{{ $brand->mobile }}</a>
                            </td>
                        </tr>
                        @endif
                        @if($brand->email)
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-mail"></i>Email:
                            </td>
                            <td class="info-value">
                                <a href="mailto:{{ $brand->email }}" class="link-primary">{{ $brand->email }}</a>
                            </td>
                        </tr>
                        @endif
                        @if($brand->gst)
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-file-text"></i>GST:
                            </td>
                            <td class="info-value">{{ $brand->gst }}</td>
                        </tr>
                        @endif
                        @if($brand->city)
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-map-pin"></i>City:
                            </td>
                            <td class="info-value">{{ $brand->city }}</td>
                        </tr>
                        @endif
                        @if($brand->location)
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-map"></i>Location:
                            </td>
                            <td class="info-value">{{ $brand->location }}</td>
                        </tr>
                        @endif
                        @if($brand->latitude && $brand->longitude)
                        <tr class="info-row">
                            <td class="info-label">
                                <i class="icon-base ti tabler-map-pin"></i>Coordinates:
                            </td>
                            <td class="info-value">
                                <a href="https://www.google.com/maps?q={{ $brand->latitude }},{{ $brand->longitude }}" target="_blank" class="link-primary">
                                    {{ number_format($brand->latitude, 6) }}, {{ number_format($brand->longitude, 6) }}
                                    <i class="icon-base ti tabler-external-link" style="font-size: 0.7rem;"></i>
                                </a>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- User Information (if user brand) -->
    @if($brand->user)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-user-circle"></i>
                User Information
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <table class="table table-borderless">
                <tr class="info-row">
                    <td class="info-label">
                        <i class="icon-base ti tabler-id"></i>User ID:
                    </td>
                    <td class="info-value">
                        <a href="{{ route('admin.users.detail', $brand->user->id) }}" class="link-primary">
                            <strong>#{{ $brand->user->id }}</strong>
                            <i class="icon-base ti tabler-external-link" style="font-size: 0.7rem;"></i>
                        </a>
                    </td>
                </tr>
                <tr class="info-row">
                    <td class="info-label">
                        <i class="icon-base ti tabler-user"></i>Name:
                    </td>
                    <td class="info-value"><strong>{{ $brand->user->name ?? 'N/A' }}</strong></td>
                </tr>
                <tr class="info-row">
                    <td class="info-label">
                        <i class="icon-base ti tabler-mail"></i>Email:
                    </td>
                    <td class="info-value">
                        <a href="mailto:{{ $brand->user->email }}" class="link-primary">{{ $brand->user->email ?? 'N/A' }}</a>
                    </td>
                </tr>
                <tr class="info-row">
                    <td class="info-label">
                        <i class="icon-base ti tabler-phone"></i>Mobile:
                    </td>
                    <td class="info-value">
                        <a href="tel:{{ $brand->user->mobile }}" class="link-primary">{{ $brand->user->mobile ?? 'N/A' }}</a>
                    </td>
                </tr>
                <tr class="info-row">
                    <td class="info-label">
                        <i class="icon-base ti tabler-building"></i>Company Name:
                    </td>
                    <td class="info-value">{{ $brand->user->company_name ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>
    </div>
    @endif

    <!-- Brand Types -->
    @if($brand->brandTypes->count() > 0)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-tags"></i>
                Brand Types ({{ $brand->brandTypes->count() }})
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <div class="d-flex flex-wrap gap-2">
                @foreach($brand->brandTypes as $brandType)
                    <span class="badge-type" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff;">
                        <i class="icon-base ti tabler-tag"></i>
                        {{ $brandType->name }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Inquiries -->
    @if($brand->inquiries->count() > 0)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-file-text"></i>
                Inquiries ({{ $brand->inquiries->count() }})
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Intent</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($brand->inquiries as $inquiry)
                        <tr>
                            <td><strong style="color: #667eea;">#{{ $inquiry->id }}</strong></td>
                            <td>
                                <span class="badge bg-info" style="padding: 0.5rem 1rem; font-weight: 700;">{{ $inquiry->inquiry_type ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary" style="padding: 0.5rem 1rem; font-weight: 700;">{{ $inquiry->intent ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-success" style="padding: 0.5rem 1rem; font-weight: 700;">{{ $inquiry->status ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $inquiry->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="card detail-card">
        <div class="card-body" style="padding: 2rem;">
            <div class="empty-state">
                <i class="icon-base ti tabler-file-off"></i>
                <p style="font-size: 1.1rem; font-weight: 600;">No inquiries found</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
