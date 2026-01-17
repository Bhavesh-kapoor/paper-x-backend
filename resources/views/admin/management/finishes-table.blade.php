<table class="table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Name</th>
            <th>Type</th>
            <th>Material</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($finishes as $finish)
        <tr>
            <td>{{ $loop->index + $finishes->firstItem() }}</td>
            <td>
                <strong style="color: #667eea;">{{ $finish->name ?? 'N/A' }}</strong>
            </td>
            <td>
                @if($finish->type)
                    <span class="badge bg-info">{{ $finish->type }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($finish->material)
                    <span class="badge bg-primary">{{ $finish->material->name }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>{{ $finish->created_at ? $finish->created_at->format('d M Y') : 'N/A' }}</td>
            <td>
                <div class="d-flex gap-2">
                    <button type="button" class="btn-edit btn-edit-finish" 
                            data-id="{{ $finish->id }}"
                            data-name="{{ $finish->name ?? '' }}"
                            data-type="{{ $finish->type ?? '' }}"
                            data-material-id="{{ $finish->material_id ?? '' }}">
                        <i class="icon-base ti tabler-edit"></i>Edit
                    </button>
                    <button type="button" class="btn-delete btn-delete-finish" 
                            data-id="{{ $finish->id }}">
                        <i class="icon-base ti tabler-trash"></i>Delete
                    </button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                    <i class="icon-base ti tabler-paint" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <p class="text-muted mb-0">No finishes found</p>
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($finishes->hasPages())
    <div class="pagination-wrapper" style="padding: 1.5rem; border-top: 1px solid #e9ecef; background: #f8f9fa;">
        <div class="d-flex justify-content-between align-items-center">
            <div class="pagination-info" style="font-size: 0.9rem; color: #6c757d;">
                Showing {{ $finishes->firstItem() }} to {{ $finishes->lastItem() }} of {{ $finishes->total() }} results
            </div>
            <div class="pagination-links">
                {{ $finishes->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endif

