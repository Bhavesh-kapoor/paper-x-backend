@extends('admin.layout')

@section('content')
    <style>
        .pr-filters-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
            background: #ffffff;
            margin-bottom: 1.5rem;
            min-width: 0;
            max-width: 100%;
        }

        .pr-filters-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            border-radius: 16px 16px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .pr-filters-body {
            padding: 1.25rem 1.5rem 1.5rem 1.5rem;
            min-width: 0;
            overflow-x: auto;
        }

        .pr-filters-body .row {
            min-width: 0;
        }

        .pr-date-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: stretch;
        }

        .pr-date-row .pr-date-input {
            flex: 1 1 140px;
            min-width: 0;
            max-width: 100%;
        }

        @media (max-width: 575.98px) {
            .pr-filters-body {
                padding: 1rem 1rem 1.25rem;
            }

            .pr-filter-actions {
                flex-direction: column;
                align-items: stretch !important;
            }

            .pr-filter-actions .pr-btn-filter,
            .pr-filter-actions .pr-btn-reset {
                width: 100%;
                justify-content: center;
            }
        }

        .pr-filter-label {
            font-weight: 600;
            color: #111827;
            font-size: 0.82rem;
            margin-bottom: 0.4rem;
        }

        .pr-filter-input {
            width: 100%;
            padding: 0.55rem 0.9rem;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.88rem;
            transition: all 0.2s ease;
        }

        .pr-filter-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
        }

        .pr-btn-filter {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            color: #ffffff;
            padding: 0.55rem 1.3rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
        }

        .pr-btn-filter:hover {
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
        }

        .pr-btn-reset {
            border-radius: 999px;
            font-weight: 500;
            font-size: 0.85rem;
            padding: 0.55rem 1.3rem;
        }

        .pr-table-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }

        .pr-table-card .card-header {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            padding: 1rem 1.5rem;
            color: #ffffff;
        }

        .pr-table-card .card-header h5 {
            margin: 0;
            font-size: 0.98rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            min-width: 0;
        }

        .pr-table thead th {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7280;
            white-space: nowrap;
            padding: 0.9rem 1rem;
        }

        .pr-table tbody td {
            padding: 0.9rem 1rem;
            font-size: 0.88rem;
            color: #111827;
            border-bottom: 1px solid #f3f4f6;
        }

        .pr-table tbody tr {
            transition: background 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        }

        .pr-table tbody tr:hover {
            background: #f9fafb;
            transform: translateY(-1px);
            box-shadow: 0 1px 6px rgba(15, 23, 42, 0.06);
        }

        .pr-badge-role {
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(59, 130, 246, 0.12);
            color: #1d4ed8;
        }

        .pr-badge-status-pending {
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(245, 158, 11, 0.12);
            color: #b45309;
        }

        .pr-badge-status-consumed {
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
        }

        .pr-badge-status-ignored {
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(107, 114, 128, 0.12);
            color: #374151;
        }

        .pr-btn-ignore {
            border-radius: 999px;
            font-size: 0.78rem;
            padding: 0.35rem 0.9rem;
        }

        .pagination-wrapper {
            padding: 1.25rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        .pagination {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pagination .page-link {
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            color: #667eea;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            height: 44px;
        }

        .pagination .page-link:hover {
            background: #667eea;
            color: #ffffff;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            background: #f8f9fa;
            border-color: #e9ecef;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .pagination .page-item.disabled .page-link:hover {
            transform: none;
            box-shadow: none;
        }

        .pagination .page-link i {
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1;
        }

        .pr-page-root {
            min-width: 0;
            max-width: 100%;
        }

        .pagination-wrapper .d-flex {
            min-width: 0;
        }

        @media (max-width: 767.98px) {
            .pagination-wrapper .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
            }

            .pagination-wrapper .pagination {
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
            }
        }
    </style>

    <div class="container-xxl flex-grow-1 container-p-y pr-page-root">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1 text-black">Pre-Registered Users</h4>
                <p class="text-muted mb-0" style="font-size: 0.9rem;">
                    Leads collected from the public pre-registration form before they complete app onboarding.
                </p>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <a href="{{ route('admin.pre-registrations.export', request()->query()) }}"
                   class="btn btn-outline-primary btn-sm"
                   style="font-weight: 600; border-radius: 10px;">
                    <i class="icon-base ti tabler-download me-1"></i>Export CSV
                </a>
                <div class="badge bg-primary" style="font-size: 0.85rem;">
                    Total Leads: {{ $preRegistrations->total() }}
                </div>
            </div>
        </div>

        <div class="card pr-filters-card">
            <div class="pr-filters-header">
                <h6 class="mb-0" style="font-weight: 600; color: #111827; font-size: 0.9rem;">
                    <i class="icon-base ti tabler-filter me-2" style="color: #6366f1;"></i>
                    Filters
                </h6>
            </div>
            <div class="pr-filters-body">
                <form method="GET" class="row g-3">
                    <div class="col-12 col-md-6 col-xl-4">
                        <label class="pr-filter-label">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="pr-filter-input"
                               placeholder="Name, email, mobile, company">
                    </div>
                    <div class="col-12 col-sm-6 col-md-6 col-xl-2">
                        <label class="pr-filter-label">Role</label>
                        <select name="role" class="pr-filter-input">
                            <option value="">All roles</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(request('role') === $role)>
                                    {{ ucfirst($role) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-6 col-xl-2">
                        <label class="pr-filter-label">Status</label>
                        <select name="status" class="pr-filter-input">
                            <option value="">All statuses</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                            <option value="consumed" @selected(request('status') === 'consumed')>Consumed</option>
                            <option value="ignored" @selected(request('status') === 'ignored')>Ignored</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-6 col-xl-2">
                        <label class="pr-filter-label">Linked user</label>
                        <select name="has_user" class="pr-filter-input">
                            <option value="">All</option>
                            <option value="yes" @selected(request('has_user') === 'yes')>Has user</option>
                            <option value="no" @selected(request('has_user') === 'no')>No user</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-6 col-xl-4">
                        <label class="pr-filter-label">Date range</label>
                        <div class="pr-date-row">
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="pr-filter-input pr-date-input" aria-label="From date">
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="pr-filter-input pr-date-input" aria-label="To date">
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2 mt-1 pr-filter-actions flex-wrap">
                        <button type="submit" class="pr-btn-filter">
                            <i class="icon-base ti tabler-filter"></i>Apply
                        </button>
                        <a href="{{ route('admin.pre-registrations') }}" class="btn btn-light pr-btn-reset">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card pr-table-card">
            <div class="card-header">
                <h5>
                    <i class="icon-base ti tabler-users"></i>
                    Pre-Registered Users
                    <span class="badge bg-light text-dark ms-2" style="font-size: 0.75rem;">
                        {{ $preRegistrations->total() }} Total
                    </span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table pr-table mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Created At</th>
                        <th>Name / Company</th>
                        <th>Mobile / Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Linked User</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($preRegistrations as $lead)
                        <tr>
                            <td>{{ $lead->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $lead->full_name }}</div>
                                <div class="text-muted small">{{ $lead->company_name }}</div>
                            </td>
                            <td>
                                <div>{{ $lead->mobile }}</div>
                                <div class="text-muted small">{{ $lead->email }}</div>
                            </td>
                            <td>
                                @if ($lead->primary_role)
                                    <span class="badge bg-info text-dark">
                                        {{ ucfirst($lead->primary_role) }}
                                    </span>
                                @else
                                    <span class="text-muted small">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if ($lead->ignored_at)
                                    <span class="badge bg-secondary">Ignored</span>
                                @elseif ($lead->consumed_at)
                                    <span class="badge bg-success">Consumed</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if ($lead->user_id)
                                    <a href="{{ route('admin.users.detail', $lead->user_id) }}" class="small">
                                        #{{ $lead->user_id }}
                                    </a>
                                @else
                                    <span class="text-muted small">None</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if (is_null($lead->ignored_at) && is_null($lead->consumed_at))
                                    <form method="POST" action="{{ route('admin.pre-registrations.ignore', $lead->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            Ignore
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No pre-registrations found.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($preRegistrations->total() > 0)
                <div class="pagination-wrapper">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <p class="mb-0 text-muted" style="font-size: 0.9rem; font-weight: 500;">
                                <i class="icon-base ti tabler-info-circle me-1"></i>
                                Showing <strong>{{ $preRegistrations->firstItem() ?? 0 }}</strong> to
                                <strong>{{ $preRegistrations->lastItem() ?? 0 }}</strong> of
                                <strong>{{ $preRegistrations->total() }}</strong> results
                            </p>
                        </div>
                        @if ($preRegistrations->hasPages())
                            <div>
                                {{ $preRegistrations->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

