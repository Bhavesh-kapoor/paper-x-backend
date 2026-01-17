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

    .stat-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
        border-color: #667eea;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #6c757d;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
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
        font-size: 1rem;
        font-weight: 500;
        margin-left: 1.75rem;
    }

    .badge-custom {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .badge-type-material {
        background: #cfe2ff;
        color: #084298;
    }

    .badge-type-machine {
        background: #d1e7dd;
        color: #0f5132;
    }

    .badge-type-job {
        background: #fff3cd;
        color: #856404;
    }

    .badge-intent-buy {
        background: #cfe2ff;
        color: #084298;
    }

    .badge-intent-sell {
        background: #d1e7dd;
        color: #0f5132;
    }

    .badge-status {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .badge-status-draft {
        background: #e9ecef;
        color: #495057;
    }

    .badge-status-matching {
        background: #fff3cd;
        color: #856404;
    }

    .badge-status-session-locked {
        background: #cfe2ff;
        color: #084298;
    }

    .badge-status-deal-won {
        background: #d1e7dd;
        color: #0f5132;
    }

    .badge-status-deal-lost {
        background: #f8d7da;
        color: #842029;
    }

    .badge-urgency {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .badge-urgency-normal {
        background: #e9ecef;
        color: #495057;
    }

    .badge-urgency-urgent {
        background: #f8d7da;
        color: #842029;
    }

    .table-custom {
        border-radius: 12px;
        overflow: hidden;
    }

    .table-custom thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
    }

    .table-custom thead th {
        color: #ffffff;
        font-weight: 600;
        padding: 1rem;
        border: none;
    }

    .table-custom tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }

    .table-custom tbody tr:hover {
        background: #f8f9fa;
    }

    .btn-back {
        background: #6c757d;
        border: none;
        color: #ffffff;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .btn-back:hover {
        background: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
        color: #ffffff;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 4rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold" style="color: #212529;">
            <i class="icon-base ti tabler-file-text me-2"></i>Inquiry Details
        </h4>
        <a href="{{ route('admin.inquiries') }}" class="btn-back">
            <i class="icon-base ti tabler-arrow-left"></i>Back to Inquiries
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value">{{ $totalQuotations }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-file-invoice"></i>Total Quotations
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value">{{ $totalResponses }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-message"></i>Total Responses
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value">{{ $totalAcceptances }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-check"></i>Total Acceptances
                </div>
            </div>
        </div>
    </div>

    <!-- Basic Information -->
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-info-circle"></i>Basic Information
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-id"></i>Inquiry ID
                </div>
                <div class="info-value">
                    <strong style="color: #667eea;">#{{ $inquiry->id }}</strong>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-file-text"></i>Title
                </div>
                <div class="info-value">{{ $inquiry->title ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-file-description"></i>Description
                </div>
                <div class="info-value">{{ $inquiry->description ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-tag"></i>Type
                </div>
                <div class="info-value">
                    @if($inquiry->inquiry_type)
                        @if($inquiry->inquiry_type->value === 'MATERIAL')
                            <span class="badge-custom badge-type-material">{{ $inquiry->inquiry_type->value }}</span>
                        @elseif($inquiry->inquiry_type->value === 'MACHINE')
                            <span class="badge-custom badge-type-machine">{{ $inquiry->inquiry_type->value }}</span>
                        @elseif($inquiry->inquiry_type->value === 'JOB')
                            <span class="badge-custom badge-type-job">{{ $inquiry->inquiry_type->value }}</span>
                        @else
                            <span class="badge bg-secondary">{{ $inquiry->inquiry_type->value }}</span>
                        @endif
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-arrow-left-right"></i>Intent
                </div>
                <div class="info-value">
                    @if($inquiry->intent)
                        @if($inquiry->intent->value === 'BUY')
                            <span class="badge-custom badge-intent-buy">{{ $inquiry->intent->value }}</span>
                        @elseif($inquiry->intent->value === 'SELL')
                            <span class="badge-custom badge-intent-sell">{{ $inquiry->intent->value }}</span>
                        @else
                            <span class="badge bg-secondary">{{ $inquiry->intent->value }}</span>
                        @endif
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-toggle-left"></i>Status
                </div>
                <div class="info-value">
                    @if($inquiry->status)
                        @php
                            $statusClass = 'badge-status-' . strtolower(str_replace('_', '-', $inquiry->status->value));
                        @endphp
                        <span class="badge-status {{ $statusClass }}">{{ $inquiry->status->value }}</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-alert-circle"></i>Urgency
                </div>
                <div class="info-value">
                    @if($inquiry->urgency)
                        @if($inquiry->urgency === 'urgent')
                            <span class="badge-urgency badge-urgency-urgent">
                                <i class="icon-base ti tabler-alert-circle"></i>{{ ucfirst($inquiry->urgency) }}
                            </span>
                        @else
                            <span class="badge-urgency badge-urgency-normal">{{ ucfirst($inquiry->urgency) }}</span>
                        @endif
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-calendar"></i>Created At
                </div>
                <div class="info-value">{{ $inquiry->created_at ? $inquiry->created_at->format('d M Y, h:i A') : 'N/A' }}</div>
            </div>
            @if($inquiry->deadline)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-clock"></i>Deadline
                </div>
                <div class="info-value">{{ $inquiry->deadline->format('d M Y, h:i A') }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Poster Information -->
    @if($inquiry->poster_id && $inquiry->poster_type)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-user"></i>Poster Information
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-tag"></i>Poster Type
                </div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $inquiry->poster_type)) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-id"></i>Poster ID
                </div>
                <div class="info-value">
                    <strong style="color: #667eea;">#{{ $inquiry->poster_id }}</strong>
                </div>
            </div>
            @if($inquiry->poster)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-info-circle"></i>Poster Details
                </div>
                <div class="info-value">
                    @if($inquiry->poster_type === 'dealer' && $inquiry->poster->user)
                        {{ $inquiry->poster->user->name ?? 'N/A' }} ({{ $inquiry->poster->user->email ?? 'N/A' }})
                    @elseif($inquiry->poster_type === 'brand' && $inquiry->poster->user)
                        {{ $inquiry->poster->user->name ?? 'N/A' }} ({{ $inquiry->poster->user->email ?? 'N/A' }})
                    @else
                        {{ class_basename($inquiry->poster_type) }} #{{ $inquiry->poster_id }}
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Brand Information -->
    @if($inquiry->brand)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-brand"></i>Brand Information
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-id"></i>Brand ID
                </div>
                <div class="info-value">
                    <a href="{{ route('admin.brands.detail', $inquiry->brand->id) }}" style="color: #667eea; text-decoration: none;">
                        <strong>#{{ $inquiry->brand->id }}</strong>
                    </a>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-building"></i>Brand Name
                </div>
                <div class="info-value">{{ $inquiry->brand->name ?? $inquiry->brand->company_name ?? 'N/A' }}</div>
            </div>
            @if($inquiry->brand->email)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-mail"></i>Email
                </div>
                <div class="info-value">
                    <a href="mailto:{{ $inquiry->brand->email }}" style="color: #667eea;">{{ $inquiry->brand->email }}</a>
                </div>
            </div>
            @endif
            @if($inquiry->brand->mobile)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-phone"></i>Mobile
                </div>
                <div class="info-value">
                    <a href="tel:{{ $inquiry->brand->mobile }}" style="color: #667eea;">{{ $inquiry->brand->mobile }}</a>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Quantity & Pricing Information -->
    @if($inquiry->quantity || $inquiry->price)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-scale"></i>Quantity & Pricing
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            @if($inquiry->quantity)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-scale"></i>Quantity
                </div>
                <div class="info-value">
                    <strong>{{ number_format($inquiry->quantity, 2) }} {{ $inquiry->quantity_unit ?? '' }}</strong>
                </div>
            </div>
            @endif
            @if($inquiry->size)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-ruler"></i>Size
                </div>
                <div class="info-value">{{ $inquiry->size }}</div>
            </div>
            @endif
            @if($inquiry->thickness)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-ruler-2"></i>Thickness
                </div>
                <div class="info-value">
                    {{ number_format($inquiry->thickness, 2) }} {{ $inquiry->thickness_unit ?? '' }}
                </div>
            </div>
            @endif
            @if($inquiry->price)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-currency-rupee"></i>Price
                </div>
                <div class="info-value">
                    <strong>₹{{ number_format($inquiry->price, 2) }} {{ $inquiry->price_unit ?? '' }}</strong>
                </div>
            </div>
            @endif
            @if($inquiry->price_negotiable !== null)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-handshake"></i>Price Negotiable
                </div>
                <div class="info-value">
                    @if($inquiry->price_negotiable)
                        <span class="badge bg-success">Yes</span>
                    @else
                        <span class="badge bg-secondary">No</span>
                    @endif
                </div>
            </div>
            @endif
            @if($inquiry->approx_price_note)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-note"></i>Approx Price Note
                </div>
                <div class="info-value">{{ $inquiry->approx_price_note }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Material/Machine/Job Specific Information -->
    @if($inquiry->inquiry_type)
        @if($inquiry->inquiry_type->value === 'MACHINE' && ($inquiry->machine_condition || $inquiry->machineListing))
        <div class="card detail-card">
            <div class="card-header">
                <h5>
                    <i class="icon-base ti tabler-tools"></i>Machine Information
                </h5>
            </div>
            <div class="card-body" style="padding: 2rem;">
                @if($inquiry->machine_condition)
                <div class="info-row">
                    <div class="info-label">
                        <i class="icon-base ti tabler-settings"></i>Machine Condition
                    </div>
                    <div class="info-value">{{ $inquiry->machine_condition }}</div>
                </div>
                @endif
                @if($inquiry->machineListing)
                <div class="info-row">
                    <div class="info-label">
                        <i class="icon-base ti tabler-id"></i>Machine Listing ID
                    </div>
                    <div class="info-value">
                        <strong style="color: #667eea;">#{{ $inquiry->machineListing->id }}</strong>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if($inquiry->inquiry_type->value === 'JOB' && ($inquiry->job_type || $inquiry->timeline_days))
        <div class="card detail-card">
            <div class="card-header">
                <h5>
                    <i class="icon-base ti tabler-briefcase"></i>Job Information
                </h5>
            </div>
            <div class="card-body" style="padding: 2rem;">
                @if($inquiry->job_type)
                <div class="info-row">
                    <div class="info-label">
                        <i class="icon-base ti tabler-tag"></i>Job Type
                    </div>
                    <div class="info-value">{{ $inquiry->job_type }}</div>
                </div>
                @endif
                @if($inquiry->timeline_days)
                <div class="info-row">
                    <div class="info-label">
                        <i class="icon-base ti tabler-calendar-time"></i>Timeline
                    </div>
                    <div class="info-value">
                        <strong>{{ $inquiry->timeline_days }} days</strong>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    @endif

    <!-- Location Information -->
    @if($inquiry->location || ($inquiry->latitude && $inquiry->longitude))
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-map-pin"></i>Location Information
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            @if($inquiry->location)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-map-pin"></i>Location
                </div>
                <div class="info-value">{{ $inquiry->location }}</div>
            </div>
            @endif
            @if($inquiry->latitude && $inquiry->longitude)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-map"></i>Coordinates
                </div>
                <div class="info-value">
                    {{ number_format($inquiry->latitude, 6) }}, {{ number_format($inquiry->longitude, 6) }}
                    <a href="https://www.google.com/maps?q={{ $inquiry->latitude }},{{ $inquiry->longitude }}" target="_blank" class="btn btn-sm btn-primary ms-2">
                        <i class="icon-base ti tabler-external-link"></i>View on Map
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Materials -->
    @if($inquiry->materials->count() > 0)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-package"></i>Materials ({{ $inquiry->materials->count() }})
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <div class="d-flex flex-wrap gap-2">
                @foreach($inquiry->materials as $material)
                    <span class="badge bg-info" style="font-size: 0.9rem; padding: 0.5rem 1rem;">{{ $material->name }}</span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Machines -->
    @if($inquiry->machines->count() > 0)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-tools"></i>Machines ({{ $inquiry->machines->count() }})
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <div class="d-flex flex-wrap gap-2">
                @foreach($inquiry->machines as $machine)
                    <span class="badge bg-success" style="font-size: 0.9rem; padding: 0.5rem 1rem;">{{ $machine->name }}</span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Quotations -->
    @if($inquiry->quotations->count() > 0)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-file-invoice"></i>Quotations ({{ $inquiry->quotations->count() }})
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Dealer</th>
                            <th>Quoted Price</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inquiry->quotations as $quotation)
                        <tr>
                            <td>#{{ $quotation->id }}</td>
                            <td>
                                @if($quotation->dealer && $quotation->dealer->user)
                                    {{ $quotation->dealer->user->name ?? 'N/A' }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                <strong>₹{{ number_format($quotation->quoted_price ?? 0, 2) }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $quotation->status ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $quotation->created_at ? $quotation->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
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
                <i class="icon-base ti tabler-file-invoice"></i>
                <p>No quotations found for this inquiry.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Session Information -->
    @if($inquiry->session)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-users"></i>Matching Session
            </h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-id"></i>Session ID
                </div>
                <div class="info-value">
                    <strong style="color: #667eea;">#{{ $inquiry->session->id }}</strong>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-toggle-left"></i>Status
                </div>
                <div class="info-value">
                    <span class="badge bg-info">{{ $inquiry->session->status ?? 'N/A' }}</span>
                </div>
            </div>
            @if($inquiry->session->created_at)
            <div class="info-row">
                <div class="info-label">
                    <i class="icon-base ti tabler-calendar"></i>Created At
                </div>
                <div class="info-value">{{ $inquiry->session->created_at->format('d M Y, h:i A') }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection

