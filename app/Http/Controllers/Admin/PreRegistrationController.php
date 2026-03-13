<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreRegistration;
use Illuminate\Http\Request;

class PreRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $query = PreRegistration::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('primary_role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->whereNull('consumed_at')->whereNull('ignored_at');
            } elseif ($request->status === 'consumed') {
                $query->whereNotNull('consumed_at');
            } elseif ($request->status === 'ignored') {
                $query->whereNotNull('ignored_at');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('has_user')) {
            if ($request->has_user === 'yes') {
                $query->whereNotNull('user_id');
            } elseif ($request->has_user === 'no') {
                $query->whereNull('user_id');
            }
        }

        $preRegistrations = $query->latest()->paginate(20)->withQueryString();

        $roles = PreRegistration::whereNotNull('primary_role')->distinct()->pluck('primary_role');

        return view('admin.management.pre-registrations', compact('preRegistrations', 'roles'));
    }

    public function ignore(PreRegistration $preRegistration)
    {
        if (is_null($preRegistration->ignored_at)) {
            $preRegistration->ignored_at = now();
            $preRegistration->save();
        }

        return redirect()
            ->route('admin.pre-registrations')
            ->with('success', 'Pre-registration marked as ignored.');
    }
}

