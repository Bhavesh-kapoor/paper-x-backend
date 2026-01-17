<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th style="width: 60px;">S.No</th>
                <th>Brand</th>
                <th>Type</th>
                <th>Contact</th>
                <th>Location</th>
                <th>Status</th>
                <th>Profile</th>
                <th>Brand Types</th>
                <th>Inquiries</th>
                <th>Joined</th>
                <th style="width: 140px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($brands as $brand)
            <tr>
                <td>
                    <strong style="color: #667eea;">{{ $brands->firstItem() + $loop->index }}</strong>
                </td>
                <td>
                    <div class="user-info">
                        <div class="user-avatar">
                            {{ strtoupper(substr($brand->name ?? $brand->company_name ?? $brand->brand_name ?? 'B', 0, 1)) }}
                        </div>
                        <div class="user-details">
                            <div class="user-name">
                                {{ $brand->name ?? $brand->company_name ?? 'N/A' }}
                            </div>
                            <div class="user-email">
                                @if($brand->brand_name)
                                    {{ $brand->brand_name }}
                                @elseif($brand->user)
                                    {{ $brand->user->email ?? 'N/A' }}
                                @else
                                    {{ $brand->email ?? 'N/A' }}
                                @endif
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    @if($brand->name && !$brand->user_id)
                        <span class="badge bg-primary">Mill Brand</span>
                    @elseif($brand->user_id)
                        <span class="badge bg-info">User Brand</span>
                    @else
                        <span class="badge bg-secondary">Unknown</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex flex-column gap-1">
                        @if($brand->mobile)
                        <div class="info-item">
                            <i class="icon-base ti tabler-phone"></i>
                            <span style="font-size: 0.85rem;">{{ $brand->mobile }}</span>
                        </div>
                        @elseif($brand->user && $brand->user->mobile)
                        <div class="info-item">
                            <i class="icon-base ti tabler-phone"></i>
                            <span style="font-size: 0.85rem;">{{ $brand->user->mobile }}</span>
                        </div>
                        @else
                        <span style="color: #6c757d; font-size: 0.85rem;">N/A</span>
                        @endif
                        @if($brand->email)
                        <div class="info-item" style="font-size: 0.8rem; color: #6c757d;">
                            <i class="icon-base ti tabler-mail"></i>
                            <span>{{ substr($brand->email, 0, 20) }}...</span>
                        </div>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="info-item">
                        <i class="icon-base ti tabler-map-pin"></i>
                        <span style="font-size: 0.85rem;">{{ $brand->city ?? 'N/A' }}</span>
                    </div>
                </td>
                <td>
                    @if($brand->status->value === 'ACTIVE')
                    <span class="status-badge" style="background: #d4edda; color: #155724;">
                        <i class="icon-base ti tabler-check"></i>
                        Active
                    </span>
                    @elseif($brand->status->value === 'PENDING')
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
                    @if($brand->profile_complete)
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
                    @if($brand->brandTypes->count() > 0)
                        <div class="d-flex flex-column gap-1">
                            <span class="badge bg-secondary" style="font-size: 0.75rem;">
                                {{ $brand->brandTypes->count() }} type(s)
                            </span>
                            <div style="font-size: 0.7rem; color: #6c757d;">
                                {{ $brand->brandTypes->pluck('name')->take(2)->join(', ') }}
                                @if($brand->brandTypes->count() > 2)
                                    +{{ $brand->brandTypes->count() - 2 }} more
                                @endif
                            </div>
                        </div>
                    @else
                        <span style="color: #6c757d; font-size: 0.85rem;">N/A</span>
                    @endif
                </td>
                <td>
                    <div class="info-item">
                        <i class="icon-base ti tabler-file-text" style="color: #17a2b8;"></i>
                        <span style="font-weight: 600; color: #17a2b8;">{{ $brand->inquiries->count() }}</span>
                    </div>
                </td>
                <td>
                    <div class="info-item">
                        <i class="icon-base ti tabler-calendar"></i>
                        <span style="font-size: 0.85rem;">{{ $brand->created_at->format('d M Y') }}</span>
                    </div>
                </td>
                <td>
                    <a href="{{ route('admin.brands.detail', $brand->id) }}" class="btn-view-details">
                        <i class="icon-base ti tabler-eye"></i>
                        View Details
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <i class="icon-base ti tabler-brand-off" style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem;"></i>
                        <p class="text-muted mb-0">No brands found</p>
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
                Showing <strong>{{ $brands->firstItem() ?? 0 }}</strong> to <strong>{{ $brands->lastItem() ?? 0 }}</strong> of <strong>{{ $brands->total() }}</strong> results
            </p>
        </div>
        <div>
            {{ $brands->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

