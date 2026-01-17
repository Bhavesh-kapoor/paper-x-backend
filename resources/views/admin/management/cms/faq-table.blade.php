<table class="table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Question</th>
            <th>Answer</th>
            <th>Sort Order</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($faqs as $faq)
        <tr>
            <td>{{ $loop->index + $faqs->firstItem() }}</td>
            <td>
                <strong style="color: #667eea;">{{ Str::limit($faq->question, 50) }}</strong>
            </td>
            <td>
                <span style="font-size: 0.85rem;">{{ Str::limit($faq->answer, 80) }}</span>
            </td>
            <td>
                <span class="badge bg-info">{{ $faq->sort_order ?? 0 }}</span>
            </td>
            <td>{{ isset($faq->created_at) ? \Carbon\Carbon::parse($faq->created_at)->format('d M Y') : 'N/A' }}</td>
            <td>
                <div class="d-flex gap-2">
                    <button type="button" class="btn-edit btn-edit-faq" 
                            data-id="{{ $faq->id }}"
                            data-question="{{ $faq->question }}"
                            data-answer="{{ $faq->answer }}"
                            data-sort-order="{{ $faq->sort_order ?? 0 }}">
                        <i class="icon-base ti tabler-edit"></i>Edit
                    </button>
                    <button type="button" class="btn-delete btn-delete-faq" 
                            data-id="{{ $faq->id }}">
                        <i class="icon-base ti tabler-trash"></i>Delete
                    </button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                    <i class="icon-base ti tabler-help" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <p class="text-muted mb-0">No FAQs found</p>
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($faqs->hasPages())
    <div class="pagination-wrapper" style="padding: 1.5rem; border-top: 1px solid #e9ecef; background: #f8f9fa;">
        <div class="d-flex justify-content-between align-items-center">
            <div class="pagination-info" style="font-size: 0.9rem; color: #6c757d;">
                Showing {{ $faqs->firstItem() }} to {{ $faqs->lastItem() }} of {{ $faqs->total() }} results
            </div>
            <div class="pagination-links">
                {{ $faqs->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endif

