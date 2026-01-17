<table class="table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Name</th>
            <th>Category</th>
            <th>Finishes Count</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($materials as $material)
        <tr>
            <td>{{ $loop->index + $materials->firstItem() }}</td>
            <td>
                <strong style="color: #667eea;">{{ $material->name }}</strong>
            </td>
            <td>
                @if($material->category)
                    <span class="badge bg-info">{{ $material->category }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                <span class="badge bg-primary">{{ $material->finishes->count() }}</span>
            </td>
            <td>{{ $material->created_at ? $material->created_at->format('d M Y') : 'N/A' }}</td>
            <td>
                <div class="d-flex gap-2">
                    <button type="button" class="btn-edit btn-edit-material" 
                            data-id="{{ $material->id }}"
                            data-name="{{ $material->name }}"
                            data-category="{{ $material->category ?? '' }}">
                        <i class="icon-base ti tabler-edit"></i>Edit
                    </button>
                    <button type="button" class="btn-delete btn-delete-material" 
                            data-id="{{ $material->id }}">
                        <i class="icon-base ti tabler-trash"></i>Delete
                    </button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                    <i class="icon-base ti tabler-package" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <p class="text-muted mb-0">No materials found</p>
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($materials->hasPages())
    <div class="pagination-wrapper" style="padding: 1.5rem; border-top: 1px solid #e9ecef; background: #f8f9fa;">
        <div class="d-flex justify-content-between align-items-center">
            <div class="pagination-info" style="font-size: 0.9rem; color: #6c757d;">
                Showing {{ $materials->firstItem() }} to {{ $materials->lastItem() }} of {{ $materials->total() }} results
            </div>
            <div class="pagination-links">
                {{ $materials->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endif

