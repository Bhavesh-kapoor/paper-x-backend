<table class="table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Title</th>
            <th>Content</th>
            <th>Sort Order</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($corporate as $item)
        <tr>
            <td>{{ $loop->index + $corporate->firstItem() }}</td>
            <td>
                <strong style="color: #667eea;">{{ Str::limit($item->title, 50) }}</strong>
            </td>
            <td>
                <span style="font-size: 0.85rem;">{{ Str::limit($item->content, 80) }}</span>
            </td>
            <td>
                <span class="badge bg-info">{{ $item->sort_order ?? 0 }}</span>
            </td>
            <td>{{ isset($item->created_at) ? \Carbon\Carbon::parse($item->created_at)->format('d M Y') : 'N/A' }}</td>
            <td>
                <div class="d-flex gap-2">
                    <button type="button" class="btn-edit btn-edit-corporate" 
                            data-id="{{ $item->id }}"
                            data-title="{{ $item->title }}"
                            data-content="{{ $item->content }}"
                            data-sort-order="{{ $item->sort_order ?? 0 }}">
                        <i class="icon-base ti tabler-edit"></i>Edit
                    </button>
                    <button type="button" class="btn-delete btn-delete-corporate" 
                            data-id="{{ $item->id }}">
                        <i class="icon-base ti tabler-trash"></i>Delete
                    </button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                    <i class="icon-base ti tabler-building" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <p class="text-muted mb-0">No corporate content found</p>
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($corporate->hasPages())
    <div class="pagination-wrapper" style="padding: 1.5rem; border-top: 1px solid #e9ecef; background: #f8f9fa;">
        <div class="d-flex justify-content-between align-items-center">
            <div class="pagination-info" style="font-size: 0.9rem; color: #6c757d;">
                Showing {{ $corporate->firstItem() }} to {{ $corporate->lastItem() }} of {{ $corporate->total() }} results
            </div>
            <div class="pagination-links">
                {{ $corporate->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endif

