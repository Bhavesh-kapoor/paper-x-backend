<table class="table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>User</th>
            <th>Company</th>
            <th>Contact</th>
            <th>Location</th>
            <th>Converter Types</th>
            <th>Finished Products</th>
            <th>Machines</th>
            <th>Scrap Types</th>
            <th>Raw Materials</th>
            <th>Capacity</th>
            <th>Status</th>
            <th>Profile</th>
            <th>Joined</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($converters as $converter)
        <tr>
            <td>{{ $loop->index + $converters->firstItem() }}</td>
            <td>
                <div class="user-info">
                    <div class="user-avatar">
                        {{ strtoupper(substr($converter->user->name ?? 'N', 0, 1)) }}
                    </div>
                    <div class="user-details">
                        <div class="user-name">{{ $converter->user->name ?? 'N/A' }}</div>
                        <div class="user-email">{{ $converter->user->email ?? 'N/A' }}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="info-item">
                    <i class="icon-base ti tabler-building"></i>
                    <span>{{ $converter->user->company_name ?? 'N/A' }}</span>
                </div>
            </td>
            <td>
                <div class="info-item">
                    <i class="icon-base ti tabler-phone"></i>
                    <span>{{ $converter->user->mobile ?? 'N/A' }}</span>
                </div>
            </td>
            <td>
                <div class="info-item">
                    <i class="icon-base ti tabler-map-pin"></i>
                    <span>
                        {{ $converter->factory_city ?? 'N/A' }}
                        @if($converter->factory_state)
                            , {{ $converter->factory_state }}
                        @endif
                    </span>
                </div>
            </td>
            <td>
                @if($converter->converterTypes->count() > 0)
                    <span class="badge bg-primary">{{ $converter->converterTypes->count() }}</span>
                    @if($converter->converter_type_custom)
                        <span class="badge bg-secondary">{{ $converter->converter_type_custom }}</span>
                    @endif
                @elseif($converter->converter_type_custom)
                    <span class="badge bg-secondary">{{ $converter->converter_type_custom }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($converter->finishedProducts->count() > 0)
                    <span class="badge bg-info">{{ $converter->finishedProducts->count() }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($converter->machines->count() > 0)
                    <span class="badge bg-success">{{ $converter->machines->count() }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($converter->scrapTypes->count() > 0)
                    <span class="badge bg-warning">{{ $converter->scrapTypes->count() }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($converter->rawMaterials->count() > 0)
                    <span class="badge bg-danger">{{ $converter->rawMaterials->count() }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($converter->capacity_daily || $converter->capacity_monthly)
                    <div class="info-item">
                        <i class="icon-base ti tabler-gauge"></i>
                        <span>
                            @if($converter->capacity_daily)
                                {{ number_format($converter->capacity_daily, 2) }} {{ $converter->capacity_unit ?? '' }}/day
                            @endif
                            @if($converter->capacity_monthly)
                                <br><small>{{ number_format($converter->capacity_monthly, 2) }} {{ $converter->capacity_unit ?? '' }}/month</small>
                            @endif
                        </span>
                    </div>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($converter->status->value === 'ACTIVE')
                    <span class="status-badge status-active">
                        <i class="icon-base ti tabler-check"></i>{{ $converter->status->value }}
                    </span>
                @elseif($converter->status->value === 'PENDING')
                    <span class="status-badge status-pending">
                        <i class="icon-base ti tabler-clock"></i>{{ $converter->status->value }}
                    </span>
                @else
                    <span class="status-badge status-inactive">
                        <i class="icon-base ti tabler-x"></i>{{ $converter->status->value }}
                    </span>
                @endif
            </td>
            <td>
                @if($converter->profile_complete)
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
                    <span>{{ $converter->created_at->format('d M Y') }}</span>
                </div>
            </td>
            <td>
                <a href="#" class="btn-view-details" onclick="alert('Converter detail page coming soon!'); return false;">
                    <i class="icon-base ti tabler-eye"></i>View
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="15" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                    <i class="icon-base ti tabler-users" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <p class="text-muted mb-0">No converters found</p>
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($converters->hasPages())
    <div class="mt-3 px-3">
        {{ $converters->links() }}
    </div>
@endif

