<table class="table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Brand Name</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($brands as $brand)
        <tr>
            <td>{{ $loop->index + $brands->firstItem() }}</td>
            <td>
                <strong style="color: #667eea;">{{ $brand->name ?? 'N/A' }}</strong>
            </td>
            <td>{{ $brand->created_at ? $brand->created_at->format('d M Y') : 'N/A' }}</td>
            <td>
                <div class="d-flex gap-2">
                    <button type="button" class="btn-edit btn-edit-brand" 
                            data-id="{{ $brand->id }}"
                            data-name="{{ $brand->name ?? '' }}">
                        <i class="icon-base ti tabler-edit"></i>Edit
                    </button>
                    <button type="button" class="btn-delete btn-delete-brand" 
                            data-id="{{ $brand->id }}">
                        <i class="icon-base ti tabler-trash"></i>Delete
                    </button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                    <i class="icon-base ti tabler-brand-apple" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <p class="text-muted mb-0">No brands found</p>
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($brands->hasPages())
    <div class="pagination-wrapper" style="padding: 1.5rem; border-top: 1px solid #e9ecef; background: #f8f9fa;">
        <div class="d-flex justify-content-between align-items-center">
            <div class="pagination-info" style="font-size: 0.9rem; color: #6c757d;">
                Showing {{ $brands->firstItem() }} to {{ $brands->lastItem() }} of {{ $brands->total() }} results
            </div>
            <div class="pagination-links">
                {{ $brands->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endif

