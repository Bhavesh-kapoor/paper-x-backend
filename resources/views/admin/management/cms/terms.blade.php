@extends('admin.layout')

@section('content')
<style>
    .terms-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08);
        background: #ffffff;
        overflow: hidden;
    }

    .terms-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 1.25rem 1.5rem;
        color: #ffffff;
    }

    .terms-card .card-header h5 {
        color: #ffffff;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .page-title {
        color: #212529;
        font-weight: 700;
    }

    .form-control {
        color: #212529;
        background: #ffffff;
        border: 2px solid #e9ecef;
    }

    .form-control:focus {
        border-color: #667eea;
        color: #212529;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-label {
        color: #212529;
        font-weight: 600;
    }

    .btn-save {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: #ffffff;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: #ffffff;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title">
            <i class="icon-base ti tabler-file-text me-2"></i>Terms & Conditions
        </h4>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
            <i class="icon-base ti tabler-arrow-left me-2"></i>Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card terms-card">
        <div class="card-header">
            <h5>
                <i class="icon-base ti tabler-file-text"></i>
                Manage Terms & Conditions
            </h5>
        </div>
        <div class="card-body">
            <form id="termsForm" method="POST" action="{{ route('admin.cms.terms.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Content <span class="text-danger">*</span></label>
                    <textarea name="content" id="content" class="form-control" rows="20" required style="color: #212529; background: #ffffff; display: none;">{{ $terms->content ?? '' }}</textarea>
                    <div id="editor" style="min-height: 400px; color: #212529;"></div>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn-save" id="saveBtn">
                        <i class="icon-base ti tabler-loader-2 d-none" id="saveSpinner" style="animation: spin 1s linear infinite;"></i>
                        <span id="saveBtnText">Save Terms & Conditions</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CKEditor -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>

<script>
let editor;

ClassicEditor
    .create(document.querySelector('#editor'), {
        toolbar: {
            items: [
                'heading', '|',
                'bold', 'italic', 'link', '|',
                'bulletedList', 'numberedList', '|',
                'outdent', 'indent', '|',
                'blockQuote', 'insertTable', '|',
                'undo', 'redo'
            ]
        },
        language: 'en',
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
            ]
        }
    })
    .then(instance => {
        editor = instance;
        // Set initial content
        const initialContent = document.getElementById('content').value;
        if (initialContent) {
            editor.setData(initialContent);
        }
    })
    .catch(error => {
        console.error('Error initializing CKEditor:', error);
    });

document.getElementById('termsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get content from CKEditor and set it to textarea
    if (editor) {
        document.getElementById('content').value = editor.getData();
    }
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('saveBtn');
    const spinner = document.getElementById('saveSpinner');
    const btnText = document.getElementById('saveBtnText');
    
    submitBtn.disabled = true;
    spinner.classList.remove('d-none');
    btnText.textContent = 'Saving...';
    
    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.json().catch(() => ({ success: true }));
        }
        return response.json().then(data => Promise.reject(data));
    })
    .then(data => {
        if (data.success !== false) {
            showAlert('Terms & Conditions updated successfully!', 'success');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message || 'Error saving Terms & Conditions', 'danger');
    })
    .finally(() => {
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
        btnText.textContent = 'Save Terms & Conditions';
    });
});

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="icon-base ti tabler-${type === 'success' ? 'check' : 'alert-circle'} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-xxl');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endsection

