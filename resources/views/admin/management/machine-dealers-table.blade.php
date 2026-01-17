<table class="table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>User</th>
            <th>Company</th>
            <th>Contact Person</th>
            <th>Contact</th>
            <th>Location</th>
            <th>Machine Listings</th>
            <th>Status</th>
            <th>Profile</th>
            <th>Joined</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($machineDealers as $machineDealer)
        <tr>
            <td>{{ $loop->index + $machineDealers->firstItem() }}</td>
            <td>
                <div class="user-info">
                    <div class="user-avatar">
                        {{ strtoupper(substr($machineDealer->user->name ?? 'N', 0, 1)) }}
                    </div>
                    <div class="user-details">
                        <div class="user-name">{{ $machineDealer->user->name ?? 'N/A' }}</div>
                        <div class="user-email">{{ $machineDealer->user->email ?? 'N/A' }}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="info-item">
                    <i class="icon-base ti tabler-building"></i>
                    <span>{{ $machineDealer->company_name ?? 'N/A' }}</span>
                </div>
            </td>
            <td>
                <div class="info-item">
                    <i class="icon-base ti tabler-user"></i>
                    <span>{{ $machineDealer->contact_person_name ?? 'N/A' }}</span>
                </div>
            </td>
            <td>
                <div class="info-item">
                    <i class="icon-base ti tabler-phone"></i>
                    <span>{{ $machineDealer->mobile ?? 'N/A' }}</span>
                </div>
                @if($machineDealer->email)
                <div class="info-item mt-1">
                    <i class="icon-base ti tabler-mail"></i>
                    <span style="font-size: 0.8rem;">{{ $machineDealer->email }}</span>
                </div>
                @endif
            </td>
            <td>
                <div class="info-item">
                    <i class="icon-base ti tabler-map-pin"></i>
                    <span>
                        {{ $machineDealer->city ?? 'N/A' }}
                        @if($machineDealer->location)
                            , {{ $machineDealer->location }}
                        @endif
                    </span>
                </div>
            </td>
            <td>
                @if($machineDealer->machineListings->count() > 0)
                    <span class="badge bg-primary">{{ $machineDealer->machineListings->count() }} Listings</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($machineDealer->status->value === 'ACTIVE')
                    <span class="status-badge status-active">
                        <i class="icon-base ti tabler-check"></i>{{ $machineDealer->status->value }}
                    </span>
                @elseif($machineDealer->status->value === 'PENDING')
                    <span class="status-badge status-pending">
                        <i class="icon-base ti tabler-clock"></i>{{ $machineDealer->status->value }}
                    </span>
                @else
                    <span class="status-badge status-inactive">
                        <i class="icon-base ti tabler-x"></i>{{ $machineDealer->status->value }}
                    </span>
                @endif
            </td>
            <td>
                @if($machineDealer->profile_complete)
                    <span class="status-badge status-complete">
                        <i class="icon-base ti tabler-check"></i>Complete
                    </span>
                @else
                    <span class="status-badge status-incomplete">
                        <i class="icon-base ti tabler-clock"></i>Incomplete
                    </span>
                @endif
            </td>
            <td>
                <div class="info-item">
                    <i class="icon-base ti tabler-calendar"></i>
                    <span>{{ $machineDealer->created_at->format('d M Y') }}</span>
                </div>
            </td>
            <td>
                <a href="#" class="btn-view-details" onclick="alert('Machine dealer detail page coming soon!'); return false;">
                    <i class="icon-base ti tabler-eye"></i>View
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                    <i class="icon-base ti tabler-tools" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <p class="text-muted mb-0">No machine dealers found</p>
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($machineDealers->hasPages())
    <div class="mt-3 px-3">
        {{ $machineDealers->links() }}
    </div>
@endif

