<table class="table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Title</th>
            <th>Type</th>
            <th>Intent</th>
            <th>Poster</th>
            <th>Brand</th>
            <th>Materials</th>
            <th>Machines</th>
            <th>Quantity</th>
            <th>Location</th>
            <th>Status</th>
            <th>Urgency</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($inquiries as $inquiry)
        <tr>
            <td>{{ $loop->index + $inquiries->firstItem() }}</td>
            <td>
                <div class="info-item">
                    <i class="icon-base ti tabler-file-text"></i>
                    <span style="font-weight: 600;">{{ Str::limit($inquiry->title ?? 'N/A', 40) }}</span>
                </div>
            </td>
            <td>
                @if($inquiry->inquiry_type)
                    @if($inquiry->inquiry_type->value === 'MATERIAL')
                        <span class="type-badge type-material">{{ $inquiry->inquiry_type->value }}</span>
                    @elseif($inquiry->inquiry_type->value === 'MACHINE')
                        <span class="type-badge type-machine">{{ $inquiry->inquiry_type->value }}</span>
                    @elseif($inquiry->inquiry_type->value === 'JOB')
                        <span class="type-badge type-job">{{ $inquiry->inquiry_type->value }}</span>
                    @else
                        <span class="badge bg-secondary">{{ $inquiry->inquiry_type->value }}</span>
                    @endif
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($inquiry->intent)
                    @if($inquiry->intent->value === 'BUY')
                        <span class="intent-badge intent-buy">{{ $inquiry->intent->value }}</span>
                    @elseif($inquiry->intent->value === 'SELL')
                        <span class="intent-badge intent-sell">{{ $inquiry->intent->value }}</span>
                    @else
                        <span class="badge bg-secondary">{{ $inquiry->intent->value }}</span>
                    @endif
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($inquiry->poster_id && $inquiry->poster_type)
                    <div class="info-item">
                        <i class="icon-base ti tabler-user"></i>
                        <span>
                            {{ ucfirst(str_replace('_', ' ', $inquiry->poster_type)) }} #{{ $inquiry->poster_id }}
                        </span>
                    </div>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($inquiry->brand)
                    <div class="info-item">
                        <i class="icon-base ti tabler-brand"></i>
                        <span>{{ $inquiry->brand->name ?? $inquiry->brand->company_name ?? 'N/A' }}</span>
                    </div>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($inquiry->materials->count() > 0)
                    <span class="badge bg-info">{{ $inquiry->materials->count() }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($inquiry->machines->count() > 0)
                    <span class="badge bg-success">{{ $inquiry->machines->count() }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($inquiry->quantity)
                    <div class="info-item">
                        <i class="icon-base ti tabler-scale"></i>
                        <span>{{ number_format($inquiry->quantity, 2) }} {{ $inquiry->quantity_unit ?? '' }}</span>
                    </div>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($inquiry->location)
                    <div class="info-item">
                        <i class="icon-base ti tabler-map-pin"></i>
                        <span>{{ Str::limit($inquiry->location, 20) }}</span>
                    </div>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($inquiry->status)
                    @php
                        $statusClass = 'status-' . strtolower(str_replace('_', '-', $inquiry->status->value));
                    @endphp
                    <span class="status-badge {{ $statusClass }}">
                        {{ $inquiry->status->value }}
                    </span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($inquiry->urgency)
                    @if($inquiry->urgency === 'urgent')
                        <span class="urgency-badge urgency-urgent">
                            <i class="icon-base ti tabler-alert-circle"></i>{{ ucfirst($inquiry->urgency) }}
                        </span>
                    @else
                        <span class="urgency-badge urgency-normal">
                            {{ ucfirst($inquiry->urgency) }}
                        </span>
                    @endif
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                <div class="info-item">
                    <i class="icon-base ti tabler-calendar"></i>
                    <span>{{ $inquiry->created_at->format('d M Y') }}</span>
                </div>
            </td>
            <td>
                <a href="{{ route('admin.inquiries.detail', $inquiry->id) }}" class="btn-view-details">
                    <i class="icon-base ti tabler-eye"></i>View
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="14" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                    <i class="icon-base ti tabler-file-text" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <p class="text-muted mb-0">No inquiries found</p>
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($inquiries->hasPages())
    <div class="mt-3 px-3">
        {{ $inquiries->links() }}
    </div>
@endif

