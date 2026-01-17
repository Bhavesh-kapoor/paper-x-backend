<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th style="width: 60px;">S.No</th>
                <th>Dealer</th>
                <th>Company</th>
                <th>Contact</th>
                <th>Location</th>
                <th>Status</th>
                <th>Profile</th>
                <th>Materials</th>
                <th>Machines</th>
                <th>Capacity</th>
                <th>Activity</th>
                <th>Joined</th>
                <th style="width: 140px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dealers as $dealer)
            <tr>
                <td>
                    <strong style="color: #667eea;">{{ $dealers->firstItem() + $loop->index }}</strong>
                </td>
                <td>
                    <div class="user-info">
                        <div class="user-avatar">
                            {{ strtoupper(substr($dealer->user->name ?? $dealer->user->email ?? 'D', 0, 1)) }}
                        </div>
                        <div class="user-details">
                            <div class="user-name">
                                {{ $dealer->user->name ?? 'N/A' }}
                            </div>
                            <div class="user-email">
                                {{ $dealer->user->email ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="info-item">
                        <i class="icon-base ti tabler-building"></i>
                        <span>{{ $dealer->user->company_name ?? 'N/A' }}</span>
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column gap-1">
                        <div class="info-item">
                            <i class="icon-base ti tabler-phone"></i>
                            <span style="font-size: 0.85rem;">{{ $dealer->user->mobile ?? 'N/A' }}</span>
                        </div>
                        @if($dealer->user->gst_in)
                        <div class="info-item" style="font-size: 0.8rem; color: #6c757d;">
                            <i class="icon-base ti tabler-file-text"></i>
                            <span>GST: {{ substr($dealer->user->gst_in, 0, 10) }}...</span>
                        </div>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column gap-1">
                        @if($dealer->locations->count() > 0)
                            @php
                                $primaryLocation = $dealer->locations->first();
                            @endphp
                            <div class="info-item">
                                <i class="icon-base ti tabler-map-pin"></i>
                                <span style="font-size: 0.85rem;">{{ $primaryLocation->city ?? 'N/A' }}</span>
                            </div>
                            <div style="font-size: 0.75rem; color: #6c757d; margin-left: 1.5rem;">
                                {{ $dealer->locations->count() }} location(s)
                            </div>
                        @else
                            <span style="color: #6c757d; font-size: 0.85rem;">N/A</span>
                        @endif
                    </div>
                </td>
                <td>
                    @if($dealer->status->value === 'ACTIVE')
                    <span class="status-badge" style="background: #d4edda; color: #155724;">
                        <i class="icon-base ti tabler-check"></i>
                        Active
                    </span>
                    @elseif($dealer->status->value === 'PENDING')
                    <span class="status-badge" style="background: #fff3cd; color: #856404;">
                        <i class="icon-base ti tabler-clock"></i>
                        Pending
                    </span>
                    @else
                    <span class="status-badge" style="background: #f8d7da; color: #721c24;">
                        <i class="icon-base ti tabler-x"></i>
                        Inactive
                    </span>
                    @endif
                </td>
                <td>
                    @if($dealer->profile_complete)
                    <span class="status-badge status-verified">
                        <i class="icon-base ti tabler-check"></i>
                        Complete
                    </span>
                    @else
                    <span class="status-badge status-unverified">
                        <i class="icon-base ti tabler-alert-circle"></i>
                        Incomplete
                    </span>
                    @endif
                </td>
                <td>
                    <div class="d-flex flex-column gap-1">
                        <button type="button" class="btn btn-sm p-0 text-start border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#materialsModal{{ $dealer->id }}" style="color: #667eea; font-weight: 600;">
                            <i class="icon-base ti tabler-package" style="color: #667eea;"></i>
                            <span style="font-weight: 600; color: #667eea; cursor: pointer;">{{ $dealer->materials->count() }}</span>
                            <i class="icon-base ti tabler-chevron-right ms-1" style="font-size: 0.7rem;"></i>
                        </button>
                        @if($dealer->materialDetails->count() > 0)
                        <div style="font-size: 0.75rem; color: #6c757d; margin-left: 1.5rem;">
                            {{ $dealer->materialDetails->count() }} details
                        </div>
                        @endif
                    </div>
                    
                    <!-- Materials Modal -->
                    <div class="modal fade" id="materialsModal{{ $dealer->id }}" tabindex="-1" aria-labelledby="materialsModalLabel{{ $dealer->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff;">
                                    <h5 class="modal-title" id="materialsModalLabel{{ $dealer->id }}">
                                        <i class="icon-base ti tabler-package me-2"></i>Materials - {{ $dealer->user->name ?? 'N/A' }}
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    @if($dealer->materials->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Material</th>
                                                        <th>Category</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($dealer->materials as $material)
                                                    <tr>
                                                        <td><strong>{{ $material->name }}</strong></td>
                                                        <td>{{ $material->category ?? 'N/A' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        @if($dealer->materialDetails->count() > 0)
                                        <hr>
                                        <h6 class="mb-3"><i class="icon-base ti tabler-info-circle me-2"></i>Material Details</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Material</th>
                                                        <th>Brand</th>
                                                        <th>Agent Type</th>
                                                        <th>Finishes</th>
                                                        <th>Thickness</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($dealer->materialDetails as $detail)
                                                    <tr>
                                                        <td><strong>{{ $detail->material->name ?? 'N/A' }}</strong></td>
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
                                                                <span class="badge bg-secondary">{{ count($detail->finish_ids) }} finishes</span>
                                                            @else
                                                                <span class="text-muted">N/A</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($detail->thickness_ranges && count($detail->thickness_ranges) > 0)
                                                                @foreach($detail->thickness_ranges as $range)
                                                                    <span class="badge bg-light text-dark me-1" style="font-size: 0.7rem;">
                                                                        {{ $range['min'] ?? '' }}-{{ $range['max'] ?? '' }} {{ $range['unit'] ?? '' }}
                                                                    </span>
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
                                        @endif
                                    @else
                                        <div class="text-center py-4">
                                            <i class="icon-base ti tabler-package-off" style="font-size: 3rem; color: #dee2e6;"></i>
                                            <p class="text-muted mt-2">No materials found</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="info-item">
                        <i class="icon-base ti tabler-tools" style="color: #17a2b8;"></i>
                        <span style="font-weight: 600; color: #17a2b8;">{{ $dealer->machines->count() }}</span>
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column gap-1">
                        @if($dealer->capacity_daily)
                        <div class="info-item" style="font-size: 0.85rem;">
                            <i class="icon-base ti tabler-gauge"></i>
                            <span>Daily: {{ number_format($dealer->capacity_daily, 0) }} {{ $dealer->capacity_unit ?? '' }}</span>
                        </div>
                        @endif
                        @if($dealer->capacity_monthly)
                        <div class="info-item" style="font-size: 0.85rem;">
                            <i class="icon-base ti tabler-calendar-month"></i>
                            <span>Monthly: {{ number_format($dealer->capacity_monthly, 0) }} {{ $dealer->capacity_unit ?? '' }}</span>
                        </div>
                        @endif
                        @if(!$dealer->capacity_daily && !$dealer->capacity_monthly)
                        <span style="color: #6c757d; font-size: 0.85rem;">N/A</span>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column gap-1">
                        <div class="info-item" style="font-size: 0.85rem;">
                            <i class="icon-base ti tabler-file-check" style="color: #28a745;"></i>
                            <span>Quotes: <strong>{{ $dealer->quotations->count() }}</strong></span>
                        </div>
                        <div class="info-item" style="font-size: 0.85rem;">
                            <i class="icon-base ti tabler-handshake" style="color: #ffc107;"></i>
                            <span>Accepted: <strong>{{ $dealer->acceptances->count() }}</strong></span>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="info-item">
                        <i class="icon-base ti tabler-calendar"></i>
                        <span style="font-size: 0.85rem;">{{ $dealer->created_at->format('d M Y') }}</span>
                    </div>
                </td>
                <td>
                    <a href="{{ route('admin.dealers.detail', $dealer->id) }}" class="btn-view-details">
                        <i class="icon-base ti tabler-eye"></i>
                        View Details
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="13" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <i class="icon-base ti tabler-users-off" style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem;"></i>
                        <p class="text-muted mb-0">No dealers found</p>
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
                Showing <strong>{{ $dealers->firstItem() ?? 0 }}</strong> to <strong>{{ $dealers->lastItem() ?? 0 }}</strong> of <strong>{{ $dealers->total() }}</strong> results
            </p>
        </div>
        <div>
            {{ $dealers->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

