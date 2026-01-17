<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th style="width: 60px;">S.No</th>
                <th>User</th>
                <th>Contact</th>
                <th>Role</th>
                <th>Company</th>
                <th>Location</th>
                <th>Status</th>
                <th>Joined</th>
                <th style="width: 140px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>
                    <strong style="color: #667eea;">{{ $loop->index + $users->firstItem() }}</strong>
                </td>
                <td>
                    <div class="user-info">
                        <div class="user-avatar">
                            {{ strtoupper(substr($user->name ?? $user->email ?? 'U', 0, 1)) }}
                        </div>
                        <div class="user-details">
                            <div class="user-name">
                                {{ $user->name ?? 'N/A' }}
                            </div>
                            <div class="user-email">
                                {{ $user->email ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="info-item">
                        <i class="icon-base ti tabler-phone"></i>
                        <span>{{ $user->mobile ?? 'N/A' }}</span>
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column gap-1">
                        <span class="badge-role badge-primary-role">
                            <i class="icon-base ti tabler-user"></i>
                            {{ $user->primary_role ?? 'N/A' }}
                        </span>
                        @if($user->has_secondary_role && $user->secondary_role)
                        <span class="badge-role badge-secondary-role">
                            <i class="icon-base ti tabler-user-plus"></i>
                            {{ $user->secondary_role }}
                        </span>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="info-item">
                        <i class="icon-base ti tabler-building"></i>
                        <span>{{ $user->company_name ?? 'N/A' }}</span>
                    </div>
                </td>
                <td>
                    <div class="info-item">
                        <i class="icon-base ti tabler-map-pin"></i>
                        <span>{{ $user->city ?? 'N/A' }}{{ $user->state ? ', ' . $user->state : '' }}</span>
                    </div>
                </td>
                <td>
                    @if($user->email_verified_at)
                    <span class="status-badge status-verified">
                        <i class="icon-base ti tabler-check"></i>
                        Verified
                    </span>
                    @else
                    <span class="status-badge status-unverified">
                        <i class="icon-base ti tabler-alert-circle"></i>
                        Unverified
                    </span>
                    @endif
                </td>
                <td>
                    <div class="info-item">
                        <i class="icon-base ti tabler-calendar"></i>
                        <span>{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                </td>
                <td>
                    <a href="{{ route('admin.users.detail', $user->id) }}" class="btn-view-details">
                        <i class="icon-base ti tabler-eye"></i>
                        View Details
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <i class="icon-base ti tabler-users-off" style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem;"></i>
                        <p class="text-muted mb-0">No users found</p>
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
                Showing <strong>{{ $users->firstItem() ?? 0 }}</strong> to <strong>{{ $users->lastItem() ?? 0 }}</strong> of <strong>{{ $users->total() }}</strong> results
            </p>
        </div>
        <div>
            {{ $users->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

