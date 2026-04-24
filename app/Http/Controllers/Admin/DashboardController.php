<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Dealer;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use App\Models\Brand;
use App\Models\Converter;
use App\Models\MachineDealer;
use App\Models\PreRegistration;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_dealers' => Dealer::count(),
            'total_brands' => Brand::count(),
            'total_converters' => Converter::count(),
            'total_machine_dealers' => MachineDealer::count(),
            'total_inquiries' => Inquiry::count(),
            'active_sessions' => MatchingSession::where('status', 'ACTIVE')->count(),
            'completed_sessions' => MatchingSession::where('status', 'COMPLETED')->count(),
            'total_pre_registrations' => PreRegistration::count(),
        ];

        // Chart data - User registrations over last 7 days
        $userRegistrations = [];
        $userLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $userLabels[] = $date->format('M d');
            $userRegistrations[] = User::whereDate('created_at', $date->format('Y-m-d'))->count();
        }

        // Inquiries by type
        $inquiriesByType = [
            'Material' => Inquiry::where('inquiry_type', 'MATERIAL')->count(),
            'Machine' => Inquiry::where('inquiry_type', 'MACHINE')->count(),
            'Job' => Inquiry::where('inquiry_type', 'JOB')->count(),
        ];

        // User types distribution
        $userTypes = [
            'Dealers' => Dealer::count(),
            'Brands' => Brand::whereNotNull('user_id')->count(),
            'Converters' => Converter::count(),
            'Machine Dealers' => MachineDealer::count(),
        ];

        // Activity over last 30 days (inquiries created)
        $activityData = [];
        $activityLabels = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $activityLabels[] = $date->format('M d');
            $activityData[] = Inquiry::whereDate('created_at', $date->format('Y-m-d'))->count();
        }

        return view('admin.dashboard', compact('stats', 'userRegistrations', 'userLabels', 'inquiriesByType', 'userTypes', 'activityData', 'activityLabels'));
    }
}
