@extends('admin.layout')

@section('content')
<style>
    .detail-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08);
        background: #ffffff;
        overflow: hidden;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }

    .detail-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .detail-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 1.25rem 1.5rem;
        color: #ffffff;
    }

    .detail-card .card-header h5 {
        color: #ffffff;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-row {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s ease;
    }

    .info-row:hover {
        background: #f8f9fa;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
        width: 40%;
    }

    .info-value {
        color: #212529;
        font-size: 0.9rem;
    }

    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .badge-active {
        background: #d4edda;
        color: #155724;
    }

    .badge-pending {
        background: #fff3cd;
        color: #856404;
    }

    .badge-inactive {
        background: #f8d7da;
        color: #721c24;
    }

    .badge-complete {
        background: #d4edda;
        color: #155724;
    }

    .badge-incomplete {
        background: #fff3cd;
        color: #856404;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 16px;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #212529;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
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

    .stat-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
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
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
        border-color: #667eea;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .stat-icon {
        font-size: 1.5rem;
        color: #667eea;
    }

    .table-modern {
        margin-bottom: 0;
    }

    .table-modern thead th {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #495057;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.75rem;
        border-bottom: 2px solid #e9ecef;
    }

    .table-modern tbody td {
        padding: 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
        color: #495057;
        font-size: 0.9rem;
    }

    .table-modern tbody tr {
        transition: all 0.2s ease;
    }

    .table-modern tbody tr:hover {
        background: #f8f9fa;
        transform: scale(1.01);
    }

    .material-badge {
        padding: 0.6rem 1.2rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0.25rem;
        transition: all 0.3s ease;
    }

    .material-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .user-avatar-large {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-weight: 700;
        font-size: 2.5rem;
        margin: 0 auto 1.5rem;
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        border: 4px solid #ffffff;
    }

    .info-section {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
    }

    .metric-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .metric-success {
        background: #d4edda;
        color: #155724;
    }

    .metric-warning {
        background: #fff3cd;
        color: #856404;
    }

    .metric-danger {
        background: #f8d7da;
        color: #721c24;
    }

    .metric-info {
        background: #d1ecf1;
        color: #0c5460;
    }

    .link-badge {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .link-badge:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 4rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="page-header">
        <div>
            <h4 class="page-title">
                <i class="icon-base ti tabler-user" style="color: #667eea;"></i>
                Dealer Details - {{ $dealer->user->name ?? 'N/A' }}
            </h4>
            <p class="text-muted mb-0 mt-1" style="font-size: 0.9rem;">
                <i class="icon-base ti tabler-id me-1"></i>Dealer ID: #{{ $dealer->id }} | 
                <i class="icon-base ti tabler-user me-1 ms-2"></i>User ID: #{{ $dealer->user->id }}
            </p>
        </div>
        <a href="{{ route('admin.dealers') }}" class="btn-back">
            <i class="icon-base ti tabler-arrow-left"></i>Back to Dealers
        </a>
    </div>

    <!-- Enhanced Overview Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">{{ $dealer->materials->count() }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-package stat-icon"></i>Materials
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">{{ $dealer->machines->count() }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-tools stat-icon"></i>Machines
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">{{ $dealer->quotations->count() }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-file-check stat-icon"></i>Quotations
                </div>
                @if($totalQuotationValue > 0)
                <div style="font-size: 0.75rem; color: #6c757d; margin-top: 0.5rem;">
                    Total: ₹{{ number_format($totalQuotationValue, 2) }}
                </div>
                @endif
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">{{ $dealer->acceptances->count() }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-handshake stat-icon"></i>Acceptances
                </div>
                <div class="d-flex justify-content-center gap-2 mt-2" style="font-size: 0.7rem;">
                    <span class="metric-badge metric-success">{{ $acceptedCount }} Accepted</span>
                    <span class="metric-badge metric-warning">{{ $pendingCount }} Pending</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value" style="font-size: 1.5rem;">₹{{ number_format($totalQuotationValue, 2) }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-currency-rupee stat-icon"></i>Total Quotation Value
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value" style="font-size: 1.5rem;">₹{{ number_format($averageQuotationValue, 2) }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-chart-line stat-icon"></i>Average Quotation
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value">{{ $dealer->locations->count() }}</div>
                <div class="stat-label">
                    <i class="icon-base ti tabler-map-pin stat-icon"></i>Locations
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Dealer Information -->
        <div class="col-md-6 mb-4">
            <div class="card detail-card">
                <div class="card-header">
                    <h5>
                        <i class="icon-base ti tabler-user"></i>
                        Dealer Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="user-avatar-large">
                        {{ strtoupper(substr($dealer->user->name ?? $dealer->user->email ?? 'D', 0, 1)) }}
                    </div>
                    <table class="table table-borderless">
                        <tr class="info-row">
                            <td class="info-label">Dealer ID:</td>
                            <td class="info-value"><strong>#{{ $dealer->id }}</strong></td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Status:</td>
                            <td class="info-value">
                                @if($dealer->status->value === 'ACTIVE')
                                <span class="status-badge badge-active">
                                    <i class="icon-base ti tabler-check"></i>Active
                                </span>
                                @elseif($dealer->status->value === 'PENDING')
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
                            <td class="info-label">Profile Complete:</td>
                            <td class="info-value">
                                @if($dealer->profile_complete)
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
                            <td class="info-label">Capacity Daily:</td>
                            <td class="info-value">
                                @if($dealer->capacity_daily)
                                    <strong>{{ number_format($dealer->capacity_daily, 2) }}</strong> {{ $dealer->capacity_unit ?? '' }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Capacity Monthly:</td>
                            <td class="info-value">
                                @if($dealer->capacity_monthly)
                                    <strong>{{ number_format($dealer->capacity_monthly, 2) }}</strong> {{ $dealer->capacity_unit ?? '' }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                        </tr>
                        @if($dealer->grades && count($dealer->grades) > 0)
                        <tr class="info-row">
                            <td class="info-label">Grades:</td>
                            <td class="info-value">
                                @foreach($dealer->grades as $grade)
                                    <span class="badge bg-secondary me-1">{{ $grade }}</span>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        <tr class="info-row">
                            <td class="info-label">Created At:</td>
                            <td class="info-value">
                                <i class="icon-base ti tabler-calendar me-1"></i>{{ $dealer->created_at->format('d M Y, h:i A') }}
                            </td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Updated At:</td>
                            <td class="info-value">
                                <i class="icon-base ti tabler-clock me-1"></i>{{ $dealer->updated_at->format('d M Y, h:i A') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Information -->
        <div class="col-md-6 mb-4">
            <div class="card detail-card">
                <div class="card-header">
                    <h5>
                        <i class="icon-base ti tabler-user-circle"></i>
                        User Information
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr class="info-row">
                            <td class="info-label">User ID:</td>
                            <td class="info-value">
                                <a href="{{ route('admin.users.detail', $dealer->user->id) }}" class="link-badge">
                                    <strong>#{{ $dealer->user->id }}</strong>
                                    <i class="icon-base ti tabler-external-link ms-1" style="font-size: 0.8rem;"></i>
                                </a>
                            </td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Name:</td>
                            <td class="info-value"><strong>{{ $dealer->user->name ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Email:</td>
                            <td class="info-value">
                                <i class="icon-base ti tabler-mail me-1"></i>{{ $dealer->user->email ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Mobile:</td>
                            <td class="info-value">
                                <i class="icon-base ti tabler-phone me-1"></i>{{ $dealer->user->mobile ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Company Name:</td>
                            <td class="info-value">
                                <i class="icon-base ti tabler-building me-1"></i>{{ $dealer->user->company_name ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">GST IN:</td>
                            <td class="info-value">
                                <i class="icon-base ti tabler-file-text me-1"></i>{{ $dealer->user->gst_in ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">City:</td>
                            <td class="info-value">
                                <i class="icon-base ti tabler-map-pin me-1"></i>{{ $dealer->user->city ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">State:</td>
                            <td class="info-value">{{ $dealer->user->state ?? 'N/A' }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Operation Area:</td>
                            <td class="info-value">
                                @if($dealer->user->operation_area)
                                    <span class="badge bg-info">{{ ucfirst($dealer->user->operation_area) }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Email Verified:</td>
                            <td class="info-value">
                                @if($dealer->user->email_verified_at)
                                    <span class="status-badge badge-complete">
                                        <i class="icon-base ti tabler-check"></i>Verified
                                        <span style="font-size: 0.7rem; margin-left: 0.5rem;">
                                            ({{ $dealer->user->email_verified_at->format('d M Y') }})
                                        </span>
                                    </span>
                                @else
                                    <span class="status-badge badge-incomplete">
                                        <i class="icon-base ti tabler-alert-circle"></i>Not Verified
                                    </span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Locations -->
    @if($dealer->locations->count() > 0)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-map-pin"></i>
                Locations ({{ $dealer->locations->count() }})
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Coordinates</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dealer->locations as $location)
                        <tr>
                            <td>
                                <span class="badge bg-primary">{{ ucfirst($location->type ?? 'N/A') }}</span>
                            </td>
                            <td>{{ $location->address ?? 'N/A' }}</td>
                            <td>{{ $location->city ?? 'N/A' }}</td>
                            <td>{{ $location->state ?? 'N/A' }}</td>
                            <td>
                                @if($location->latitude && $location->longitude)
                                    <a href="https://www.google.com/maps?q={{ $location->latitude }},{{ $location->longitude }}" target="_blank" class="link-badge">
                                        {{ number_format($location->latitude, 6) }}, {{ number_format($location->longitude, 6) }}
                                        <i class="icon-base ti tabler-external-link ms-1" style="font-size: 0.7rem;"></i>
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Materials -->
    @if($dealer->materials->count() > 0)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-package"></i>
                Materials ({{ $dealer->materials->count() }})
            </h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach($dealer->materials as $material)
                    <span class="material-badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff;">
                        <i class="icon-base ti tabler-package"></i>
                        {{ $material->name }}
                        @if($material->category)
                            <span style="font-size: 0.75rem; opacity: 0.9;">({{ $material->category }})</span>
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Material Details -->
    @if($dealer->materialDetails->count() > 0)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-list-details"></i>
                Material Details ({{ $dealer->materialDetails->count() }})
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Brand/Mill</th>
                            <th>Agent Type</th>
                            <th>Finishes</th>
                            <th>Thickness Ranges</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dealer->materialDetails as $detail)
                        <tr>
                            <td>
                                <strong>{{ $detail->material->name ?? 'N/A' }}</strong>
                            </td>
                            <td>
                                @if($detail->brand)
                                    <span class="badge bg-info">{{ $detail->brand->name }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($detail->agent_type)
                                    <span class="badge bg-warning text-dark">{{ $detail->agent_type }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($detail->finish_ids && count($detail->finish_ids) > 0)
                                    <span class="badge bg-secondary">{{ count($detail->finish_ids) }} finishes</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($detail->thickness_ranges && count($detail->thickness_ranges) > 0)
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($detail->thickness_ranges as $range)
                                            <span class="badge bg-light text-dark" style="font-size: 0.75rem;">
                                                {{ $range['min'] ?? '' }}-{{ $range['max'] ?? '' }} {{ $range['unit'] ?? '' }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Machines -->
    @if($dealer->machines->count() > 0)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-tools"></i>
                Machines ({{ $dealer->machines->count() }})
            </h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                @foreach($dealer->machines as $machine)
                    <span class="material-badge" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: #ffffff;">
                        <i class="icon-base ti tabler-tools"></i>
                        {{ $machine->name }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Acceptances -->
    @if($dealer->acceptances->count() > 0)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-handshake"></i>
                Acceptances ({{ $dealer->acceptances->count() }})
                <span class="badge bg-light text-dark ms-2">
                    {{ $acceptedCount }} Accepted | {{ $pendingCount }} Pending | {{ $declinedCount }} Declined
                </span>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Inquiry</th>
                            <th>Status</th>
                            <th>Decline Reason</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dealer->acceptances as $acceptance)
                        <tr>
                            <td><strong>#{{ $acceptance->id }}</strong></td>
                            <td>
                                @if($acceptance->inquiry)
                                    <a href="#" class="link-badge">
                                        Inquiry #{{ $acceptance->inquiry->id }}
                                        <i class="icon-base ti tabler-external-link ms-1" style="font-size: 0.7rem;"></i>
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($acceptance->status)
                                    @if($acceptance->status->value === 'ACCEPTED')
                                        <span class="badge bg-success">{{ $acceptance->status->value }}</span>
                                    @elseif($acceptance->status->value === 'PENDING')
                                        <span class="badge bg-warning text-dark">{{ $acceptance->status->value }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ $acceptance->status->value }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($acceptance->decline_reason)
                                    <span class="text-muted" style="font-size: 0.85rem;">{{ $acceptance->decline_reason }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $acceptance->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="card detail-card">
        <div class="card-body">
            <div class="empty-state">
                <i class="icon-base ti tabler-handshake-off"></i>
                <p>No acceptances found</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Quotations -->
    @if($dealer->quotations->count() > 0)
    <div class="card detail-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-file-check"></i>
                Quotations ({{ $dealer->quotations->count() }})
                <span class="badge bg-light text-dark ms-2">
                    Total: ₹{{ number_format($totalQuotationValue, 2) }} | Avg: ₹{{ number_format($averageQuotationValue, 2) }}
                </span>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Inquiry</th>
                            <th>Session</th>
                            <th>Quoted Price</th>
                            <th>Currency</th>
                            <th>Delivery Days</th>
                            <th>Deal Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dealer->quotations as $quotation)
                        <tr>
                            <td><strong>#{{ $quotation->id }}</strong></td>
                            <td>
                                @if($quotation->inquiry)
                                    <a href="#" class="link-badge">
                                        Inquiry #{{ $quotation->inquiry->id }}
                                        <i class="icon-base ti tabler-external-link ms-1" style="font-size: 0.7rem;"></i>
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($quotation->session)
                                    <span class="badge bg-info">Session #{{ $quotation->session->id }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($quotation->quoted_price)
                                    <strong style="color: #28a745;">₹{{ number_format($quotation->quoted_price, 2) }}</strong>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $quotation->currency ?? 'INR' }}</span>
                            </td>
                            <td>
                                @if($quotation->delivery_days)
                                    <span class="badge bg-info">{{ $quotation->delivery_days }} days</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($quotation->deal_status)
                                    @if($quotation->deal_status->value === 'COMPLETED')
                                        <span class="badge bg-success">{{ $quotation->deal_status->value }}</span>
                                    @elseif($quotation->deal_status->value === 'PENDING')
                                        <span class="badge bg-warning text-dark">{{ $quotation->deal_status->value }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ $quotation->deal_status->value }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $quotation->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="card detail-card">
        <div class="card-body">
            <div class="empty-state">
                <i class="icon-base ti tabler-file-off"></i>
                <p>No quotations found</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
