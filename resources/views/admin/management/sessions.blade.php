@extends('admin.layout')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">{{ request()->routeIs('admin.sessions.active') ? 'Active' : 'Completed' }} Sessions</h4>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
            <i class="icon-base ti tabler-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Inquiry ID</th>
                            <th>Status</th>
                            <th>Dealers Count</th>
                            <th>Discovery Start</th>
                            <th>Active Session Start</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $session)
                        <tr>
                            <td>{{ $session->id }}</td>
                            <td>{{ $session->inquiry_id }}</td>
                            <td>
                                <span class="badge bg-{{ $session->status->value === 'ACTIVE' ? 'success' : ($session->status->value === 'COMPLETED' ? 'primary' : 'secondary') }}">
                                    {{ $session->status->value }}
                                </span>
                            </td>
                            <td>{{ $session->acceptances->count() ?? 0 }}</td>
                            <td>{{ $session->discovery_start ? $session->discovery_start->format('d M Y H:i') : 'N/A' }}</td>
                            <td>{{ $session->active_session_start ? $session->active_session_start->format('d M Y H:i') : 'N/A' }}</td>
                            <td>{{ $session->created_at->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No sessions found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $sessions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

