<table class="table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Name</th>
            <th>Type</th>
            <th>Description</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($machines as $machine)
        <tr>
            <td>{{ $loop->index + $machines->firstItem() }}</td>
            <td>
                <strong style="color: #667eea;">{{ $machine->name }}</strong>
            </td>
            <td>
                @if($machine->type)
                    <span class="badge bg-info">{{ $machine->type }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($machine->description)
                    <span style="font-size: 0.85rem;">{{ Str::limit($machine->description, 50) }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td>{{ $machine->created_at ? $machine->created_at->format('d M Y') : 'N/A' }}</td>
            <td>
                <div class="d-flex gap-2">
                    <button type="button" class="btn-edit btn-edit-machine" 
                            data-id="{{ $machine->id }}"
                            data-name="{{ $machine->name }}"
                            data-type="{{ $machine->type ?? '' }}"
                            data-description="{{ $machine->description ?? '' }}">
                        <i class="icon-base ti tabler-edit"></i>Edit
                    </button>
                    <button type="button" class="btn-delete btn-delete-machine" 
                            data-id="{{ $machine->id }}">
                        <i class="icon-base ti tabler-trash"></i>Delete
                    </button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                    <i class="icon-base ti tabler-tools" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <p class="text-muted mb-0">No machines found</p>
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($machines->hasPages())
    <div class="pagination-wrapper" style="padding: 1.5rem; border-top: 1px solid #e9ecef; background: #f8f9fa;">
        <div class="d-flex justify-content-between align-items-center">
            <div class="pagination-info" style="font-size: 0.9rem; color: #6c757d;">
                Showing {{ $machines->firstItem() }} to {{ $machines->lastItem() }} of {{ $machines->total() }} results
            </div>
            <div class="pagination-links">
                {{ $machines->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endif

