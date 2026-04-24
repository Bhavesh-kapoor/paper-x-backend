<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PreRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $preRegistrations = $this->filteredQuery($request)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $roles = PreRegistration::whereNotNull('primary_role')->distinct()->pluck('primary_role');

        return view('admin.management.pre-registrations', compact('preRegistrations', 'roles'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request)->orderByDesc('created_at');
        $filename = 'pre-registrations-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Created At',
                'Full Name',
                'Company',
                'Mobile',
                'Email',
                'Role',
                'Status',
                'User ID',
                'Consumed At',
                'Ignored At',
                'UTM Source',
                'UTM Medium',
                'UTM Campaign',
                'IP Address',
                'Notes',
            ]);

            foreach ($query->cursor() as $lead) {
                $status = 'pending';
                if ($lead->ignored_at) {
                    $status = 'ignored';
                } elseif ($lead->consumed_at) {
                    $status = 'consumed';
                }

                fputcsv($out, [
                    $lead->created_at?->format('Y-m-d H:i:s'),
                    $lead->full_name,
                    $lead->company_name,
                    $lead->mobile,
                    $lead->email,
                    $lead->primary_role,
                    $status,
                    $lead->user_id,
                    $lead->consumed_at?->format('Y-m-d H:i:s'),
                    $lead->ignored_at?->format('Y-m-d H:i:s'),
                    $lead->utm_source,
                    $lead->utm_medium,
                    $lead->utm_campaign,
                    $lead->ip_address,
                    $lead->notes,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    protected function filteredQuery(Request $request): Builder
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

        return $query;
    }
}
