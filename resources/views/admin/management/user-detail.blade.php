@extends('admin.layout')

@section('content')
<style>
    .user-detail-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .user-detail-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }
    .user-detail-card .card-header {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        color: white;
        border: none;
        padding: 1.25rem;
        font-weight: 600;
    }
    .user-detail-card .card-body {
        padding: 1.5rem;
    }
    .info-table tr {
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
    }
    .info-table tr:hover {
        background: #f8f9fa;
    }
    .info-table th {
        color: #64748b;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .info-table td {
        color: #1e293b;
        font-weight: 500;
    }
    .badge-custom {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 500;
    }
    .section-title {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        color: white;
        padding: 1rem 1.5rem;
        margin: 0;
        border-radius: 12px 12px 0 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .section-title i {
        font-size: 1.2rem;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #1e40af;">User Details</h4>
            <p class="text-muted mb-0">Complete information about the user</p>
        </div>
        <a href="{{ route('admin.users') }}" class="btn btn-primary">
            <i class="icon-base ti tabler-arrow-left me-2"></i>Back to Users
        </a>
    </div>

    <div class="row">
        <!-- Basic Information -->
        <div class="col-md-6 mb-4">
            <div class="card user-detail-card">
                <div class="card-header">
                    <i class="icon-base ti tabler-user me-2"></i>Basic Information
                </div>
                <div class="card-body">
                    <table class="table table-borderless info-table">
                        <tr>
                            <th width="40%">ID:</th>
                            <td>{{ $user->id }}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>{{ $user->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>{{ $user->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Mobile:</th>
                            <td>{{ $user->mobile ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Primary Role:</th>
                            <td>
                                <span class="badge bg-primary">{{ $user->primary_role ?? 'N/A' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Secondary Role:</th>
                            <td>
                                @if($user->has_secondary_role && $user->secondary_role)
                                    <span class="badge bg-info">{{ $user->secondary_role }}</span>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Email Verified:</th>
                            <td>
                                @if($user->email_verified_at)
                                    <span class="badge bg-success">Verified</span>
                                    <small class="text-muted">({{ $user->email_verified_at->format('d M Y') }})</small>
                                @else
                                    <span class="badge bg-warning">Not Verified</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td>{{ $user->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Company Information -->
        <div class="col-md-6 mb-4">
            <div class="card user-detail-card">
                <div class="card-header">
                    <i class="icon-base ti tabler-building me-2"></i>Company Information
                </div>
                <div class="card-body">
                    <table class="table table-borderless info-table">
                        <tr>
                            <th width="40%">Company Name:</th>
                            <td>{{ $user->company_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>GST IN:</th>
                            <td>{{ $user->gst_in ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>State:</th>
                            <td>{{ $user->state ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>City:</th>
                            <td>{{ $user->city ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Operation Area:</th>
                            <td>{{ $user->operation_area ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Udyam Certificate:</th>
                            <td>
                                @if($user->udyam_certificate)
                                    <span class="badge bg-success">Uploaded</span>
                                    @if($user->udyam_verified_at)
                                        <span class="badge bg-info ms-1">Verified</span>
                                    @endif
                                @else
                                    <span class="text-muted">Not Uploaded</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Dealer Profile -->
    @if($user->dealer)
    <div class="card user-detail-card mb-4">
        <h5 class="section-title">
            <i class="icon-base ti tabler-truck"></i>Dealer Profile
        </h5>
        <div class="card-body">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless info-table">
                            <tr>
                                <th width="40%">Status:</th>
                                <td>
                                    <span class="badge bg-{{ $user->dealer->status->value === 'ACTIVE' ? 'success' : 'secondary' }}">
                                        {{ $user->dealer->status->value }}
                                    </span>
                                </td>
                            </tr>
                        <tr>
                            <th>Profile Complete:</th>
                            <td>
                                <span class="badge bg-{{ $user->dealer->profile_complete ? 'success' : 'warning' }}">
                                    {{ $user->dealer->profile_complete ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Capacity Daily:</th>
                            <td>{{ $user->dealer->capacity_daily ?? 'N/A' }} {{ $user->dealer->capacity_unit ?? '' }}</td>
                        </tr>
                        <tr>
                            <th>Capacity Monthly:</th>
                            <td>{{ $user->dealer->capacity_monthly ?? 'N/A' }} {{ $user->dealer->capacity_unit ?? '' }}</td>
                        </tr>
                        @if($user->dealer->grades)
                        <tr>
                            <th>Grades:</th>
                            <td>
                                @foreach($user->dealer->grades as $grade)
                                    <span class="badge bg-secondary me-1">{{ $grade }}</span>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        </table>
                    </div>
                </div>

                <!-- Locations -->
            @if($user->dealer->locations->count() > 0)
                <div class="mt-4">
                    <h6 class="mb-3 fw-bold" style="color: #1e40af;">
                        <i class="icon-base ti tabler-map-pin me-2"></i>Locations ({{ $user->dealer->locations->count() }})
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
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
                            @foreach($user->dealer->locations as $location)
                            <tr>
                                <td>{{ $location->type ?? 'N/A' }}</td>
                                <td>{{ $location->address ?? 'N/A' }}</td>
                                <td>{{ $location->city ?? 'N/A' }}</td>
                                <td>{{ $location->state ?? 'N/A' }}</td>
                                <td>
                                    @if($location->latitude && $location->longitude)
                                        {{ $location->latitude }}, {{ $location->longitude }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Materials -->
                @if($user->dealer->materials->count() > 0)
                <div class="mt-4">
                    <h6 class="mb-3 fw-bold" style="color: #1e40af;">
                        <i class="icon-base ti tabler-package me-2"></i>Materials ({{ $user->dealer->materials->count() }})
                    </h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($user->dealer->materials as $material)
                        <span class="badge bg-primary">{{ $material->name }}</span>
                    @endforeach
                    </div>
                </div>
                @endif

                <!-- Material Details -->
                @if($user->dealer->materialDetails->count() > 0)
                <div class="mt-4">
                    <h6 class="mb-3 fw-bold" style="color: #1e40af;">
                        <i class="icon-base ti tabler-list-details me-2"></i>Material Details ({{ $user->dealer->materialDetails->count() }})
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Brand</th>
                                <th>Agent Type</th>
                                <th>Finishes</th>
                                <th>Thickness Ranges</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->dealer->materialDetails as $detail)
                            <tr>
                                <td>{{ $detail->material->name ?? 'N/A' }}</td>
                                <td>{{ $detail->brand->name ?? 'N/A' }}</td>
                                <td>
                                    @if($detail->agent_type)
                                        <span class="badge bg-info">{{ $detail->agent_type }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($detail->finish_ids && count($detail->finish_ids) > 0)
                                        <small>{{ count($detail->finish_ids) }} finishes</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($detail->thickness_ranges && count($detail->thickness_ranges) > 0)
                                        @foreach($detail->thickness_ranges as $range)
                                            <small>{{ $range['min'] ?? '' }}-{{ $range['max'] ?? '' }} {{ $range['unit'] ?? '' }}</small><br>
                                        @endforeach
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
                @endif

                <!-- Machines -->
                @if($user->dealer->machines->count() > 0)
                <div class="mt-4">
                    <h6 class="mb-3 fw-bold" style="color: #1e40af;">
                        <i class="icon-base ti tabler-tools me-2"></i>Machines ({{ $user->dealer->machines->count() }})
                    </h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($user->dealer->machines as $machine)
                        <span class="badge bg-success">{{ $machine->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Brand Profile -->
    @if($user->brand)
    <div class="card user-detail-card mb-4">
        <h5 class="section-title">
            <i class="icon-base ti tabler-brand-apple"></i>Brand Profile
        </h5>
        <div class="card-body">
            <table class="table table-borderless info-table">
                <tr>
                    <th width="40%">Brand Name:</th>
                    <td>{{ $user->brand->brand_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Company Name:</th>
                    <td>{{ $user->brand->company_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Contact Person:</th>
                    <td>{{ $user->brand->contact_person_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Mobile:</th>
                    <td>{{ $user->brand->mobile ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td>{{ $user->brand->email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>GST:</th>
                    <td>{{ $user->brand->gst ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>City:</th>
                    <td>{{ $user->brand->city ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Location:</th>
                    <td>{{ $user->brand->location ?? 'N/A' }}</td>
                </tr>
                @if($user->brand->brandTypes->count() > 0)
                <tr>
                    <th>Brand Types:</th>
                    <td>
                        @foreach($user->brand->brandTypes as $type)
                            <span class="badge bg-info me-1">{{ $type->name ?? 'N/A' }}</span>
                        @endforeach
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>
    @endif

    <!-- Converter Profile -->
    @if($user->converter)
    <div class="card user-detail-card mb-4">
        <h5 class="section-title">
            <i class="icon-base ti tabler-settings"></i>Converter Profile
        </h5>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless info-table">
                        <tr>
                            <th width="40%">Status:</th>
                            <td>
                                <span class="badge bg-{{ $user->converter->status->value === 'ACTIVE' ? 'success' : 'secondary' }}">
                                    {{ $user->converter->status->value }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Profile Complete:</th>
                            <td>
                                <span class="badge bg-{{ $user->converter->profile_complete ? 'success' : 'warning' }}">
                                    {{ $user->converter->profile_complete ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Converter Type Custom:</th>
                            <td>{{ $user->converter->converter_type_custom ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Capacity Daily:</th>
                            <td>{{ $user->converter->capacity_daily ?? 'N/A' }} {{ $user->converter->capacity_unit ?? '' }}</td>
                        </tr>
                        <tr>
                            <th>Capacity Monthly:</th>
                            <td>{{ $user->converter->capacity_monthly ?? 'N/A' }} {{ $user->converter->capacity_unit ?? '' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless info-table">
                        <tr>
                            <th width="40%">Factory Address:</th>
                            <td>{{ $user->converter->factory_address ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Factory City:</th>
                            <td>{{ $user->converter->factory_city ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Factory State:</th>
                            <td>{{ $user->converter->factory_state ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Factory Coordinates:</th>
                            <td>
                                @if($user->converter->factory_latitude && $user->converter->factory_longitude)
                                    {{ $user->converter->factory_latitude }}, {{ $user->converter->factory_longitude }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($user->converter->converterTypes->count() > 0)
            <div class="mt-3">
                <h6 class="mb-2 fw-bold" style="color: #1e40af;">
                    <i class="icon-base ti tabler-tags me-2"></i>Converter Types:
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($user->converter->converterTypes as $type)
                        <span class="badge bg-info badge-custom">{{ $type->name ?? 'N/A' }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($user->converter->finishedProducts->count() > 0)
            <div class="mt-3">
                <h6 class="mb-2 fw-bold" style="color: #1e40af;">
                    <i class="icon-base ti tabler-box me-2"></i>Finished Products ({{ $user->converter->finishedProducts->count() }}):
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($user->converter->finishedProducts as $product)
                        <span class="badge bg-success">{{ $product->name ?? 'N/A' }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Machine Dealer Profile -->
    @if($user->machineDealer)
    <div class="card user-detail-card mb-4">
        <h5 class="section-title">
            <i class="icon-base ti tabler-tools"></i>Machine Dealer Profile
        </h5>
        <div class="card-body">
            <table class="table table-borderless info-table">
                <tr>
                    <th width="40%">Company Name:</th>
                    <td>{{ $user->machineDealer->company_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Contact Person:</th>
                    <td>{{ $user->machineDealer->contact_person_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Mobile:</th>
                    <td>{{ $user->machineDealer->mobile ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td>{{ $user->machineDealer->email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>GST:</th>
                    <td>{{ $user->machineDealer->gst ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>City:</th>
                    <td>{{ $user->machineDealer->city ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Location:</th>
                    <td>{{ $user->machineDealer->location ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <span class="badge bg-{{ $user->machineDealer->status->value === 'ACTIVE' ? 'success' : 'secondary' }}">
                            {{ $user->machineDealer->status->value }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Profile Complete:</th>
                    <td>
                        <span class="badge bg-{{ $user->machineDealer->profile_complete ? 'success' : 'warning' }}">
                            {{ $user->machineDealer->profile_complete ? 'Yes' : 'No' }}
                        </span>
                    </td>
                </tr>
                @if($user->machineDealer->machineListings->count() > 0)
                <tr>
                    <th>Machine Listings:</th>
                    <td>{{ $user->machineDealer->machineListings->count() }} listings</td>
                </tr>
                @endif
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

