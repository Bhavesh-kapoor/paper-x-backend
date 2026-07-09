<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Dealer;
use App\Models\Brand;
use App\Models\Converter;
use App\Models\MachineDealer;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use App\Models\Material;
use App\Models\Machine;
use App\Models\MaterialFinish;

class ManagementController extends Controller
{
    /**
     * Show all users
     */
    public function users(Request $request)
    {
        $query = User::with(['dealer', 'brand', 'converter', 'machineDealer']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->where('primary_role', $request->role);
        }

        // Status filter (email verified)
        if ($request->filled('status')) {
            if ($request->status === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        // City filter
        if ($request->filled('city')) {
            $query->where('city', 'like', "%{$request->city}%");
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $users = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $roles = User::whereNotNull('primary_role')->distinct()->pluck('primary_role');
        $cities = User::whereNotNull('city')->distinct()->pluck('city')->sort();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.users-table', compact('users'))->render(),
                'total' => $users->total()
            ]);
        }
        
        return view('admin.management.users', compact('users', 'roles', 'cities'));
    }

    /**
     * Show user detail
     */
    public function userDetail(User $user)
    {
        $user->load([
            'dealer.locations', 
            'dealer.materials', 
            'dealer.machines', 
            'dealer.materialDetails.material', 
            'dealer.materialDetails.brand', 
            'brand.brandTypes', 
            'converter.converterTypes', 
            'converter.finishedProducts',
            'converter.machines',
            'converter.scrapTypes',
            'converter.rawMaterials',
            'machineDealer.machineListings'
        ]);

        $invoiceResult = app(\App\Services\InvoiceService::class)->listForUser($user, 1, 100);
        $invoices = $invoiceResult['data'];
        $invoicesTotalPaid = collect($invoices)->sum('total_inr');

        return view('admin.management.user-detail', compact('user', 'invoices', 'invoicesTotalPaid'));
    }

    /**
     * Show all dealers
     */
    public function dealers(Request $request)
    {
        $query = Dealer::with([
            'user', 
            'locations', 
            'materialDetails',
            'materials',
            'machines',
            'acceptances',
            'quotations'
        ]);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Profile complete filter
        if ($request->filled('profile_complete')) {
            $query->where('profile_complete', $request->profile_complete === 'yes');
        }

        // City filter
        if ($request->filled('city')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('city', 'like', "%{$request->city}%");
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $dealers = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $statuses = ['PENDING', 'ACTIVE', 'INACTIVE'];
        $cities = User::whereHas('dealer')->whereNotNull('city')->distinct()->pluck('city')->sort();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.dealers-table', compact('dealers'))->render(),
                'total' => $dealers->total()
            ]);
        }
        
        return view('admin.management.dealers', compact('dealers', 'statuses', 'cities'));
    }

    /**
     * Show dealer detail
     */
    public function dealerDetail(Dealer $dealer)
    {
        $dealer->load([
            'user',
            'locations',
            'materials',
            'machines',
            'materialDetails.material',
            'materialDetails.brand',
            'acceptances.inquiry',
            'quotations.inquiry',
            'quotations.session'
        ]);
        
        // Calculate statistics
        $totalQuotationValue = $dealer->quotations()->sum('quoted_price');
        $averageQuotationValue = $dealer->quotations()->count() > 0 
            ? $dealer->quotations()->avg('quoted_price') 
            : 0;
        $acceptedCount = $dealer->acceptances()->where('status', 'ACCEPTED')->count();
        $pendingCount = $dealer->acceptances()->where('status', 'PENDING')->count();
        $declinedCount = $dealer->acceptances()->where('status', 'DECLINED')->count();
        
        return view('admin.management.dealer-detail', compact(
            'dealer', 
            'totalQuotationValue', 
            'averageQuotationValue',
            'acceptedCount',
            'pendingCount',
            'declinedCount'
        ));
    }

    /**
     * Show all brands
     */
    public function brands(Request $request)
    {
        $query = Brand::with('user', 'brandTypes', 'inquiries');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('brand_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Profile complete filter
        if ($request->filled('profile_complete')) {
            $query->where('profile_complete', $request->profile_complete === 'yes');
        }

        // Type filter (mill brand vs user brand)
        if ($request->filled('type')) {
            if ($request->type === 'mill') {
                $query->whereNotNull('name')->whereNull('user_id');
            } elseif ($request->type === 'user') {
                $query->whereNotNull('user_id');
            }
        }

        // City filter
        if ($request->filled('city')) {
            $query->where('city', 'like', "%{$request->city}%");
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $brands = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $statuses = ['PENDING', 'ACTIVE', 'INACTIVE'];
        $cities = Brand::whereNotNull('city')->distinct()->pluck('city')->sort();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.brands-table', compact('brands'))->render(),
                'total' => $brands->total()
            ]);
        }
        
        return view('admin.management.brands', compact('brands', 'statuses', 'cities'));
    }

    /**
     * Show brand detail
     */
    public function brandDetail(Brand $brand)
    {
        $brand->load([
            'user',
            'brandTypes',
            'inquiries'
        ]);
        
        // Calculate statistics
        $totalInquiries = $brand->inquiries->count();
        $activeInquiries = $brand->inquiries()->where('status', 'ACTIVE')->count();
        $completedInquiries = $brand->inquiries()->where('status', 'COMPLETED')->count();
        
        return view('admin.management.brand-detail', compact(
            'brand',
            'totalInquiries',
            'activeInquiries',
            'completedInquiries'
        ));
    }

    /**
     * Show brand edit form
     */
    public function brandEdit(Brand $brand)
    {
        $brand->load(['user', 'brandTypes']);
        $brandTypes = \App\Models\BrandType::all();
        
        return view('admin.management.brand-edit', compact('brand', 'brandTypes'));
    }

    /**
     * Update brand
     */
    public function brandUpdate(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gst' => 'nullable|string|max:15',
            'city' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'nullable|in:PENDING,ACTIVE,INACTIVE',
            'profile_complete' => 'nullable|boolean',
            'brand_type_ids' => 'nullable|array',
            'brand_type_ids.*' => 'integer|exists:brand_types,id',
        ]);

        // Convert profile_complete to boolean if it's a string
        if (isset($validated['profile_complete']) && is_string($validated['profile_complete'])) {
            $validated['profile_complete'] = $validated['profile_complete'] === '1';
        }

        $brand->update($validated);

        // Sync brand types
        if (isset($validated['brand_type_ids'])) {
            $brand->brandTypes()->sync($validated['brand_type_ids']);
        } else {
            $brand->brandTypes()->detach();
        }

        return redirect()->route('admin.brands.detail', $brand->id)
            ->with('success', 'Brand updated successfully!');
    }

    /**
     * Delete brand
     */
    public function brandDelete(Brand $brand)
    {
        $brand->delete();
        
        return redirect()->route('admin.brands')
            ->with('success', 'Brand deleted successfully!');
    }

    /**
     * Show all converters
     */
    public function converters(Request $request)
    {
        $query = Converter::with('user', 'converterTypes', 'finishedProducts', 'machines', 'scrapTypes', 'rawMaterials');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('factory_city', 'like', "%{$search}%")
                  ->orWhere('factory_state', 'like', "%{$search}%")
                  ->orWhere('factory_address', 'like', "%{$search}%")
                  ->orWhere('converter_type_custom', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Profile complete filter
        if ($request->filled('profile_complete')) {
            $query->where('profile_complete', $request->profile_complete === 'yes');
        }

        // City filter
        if ($request->filled('city')) {
            $query->where('factory_city', 'like', "%{$request->city}%");
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $converters = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $statuses = ['PENDING', 'ACTIVE', 'INACTIVE'];
        $cities = Converter::whereNotNull('factory_city')->distinct()->pluck('factory_city')->sort();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.converters-table', compact('converters'))->render(),
                'total' => $converters->total(),
                'firstItem' => $converters->firstItem(),
                'lastItem' => $converters->lastItem()
            ]);
        }
        
        return view('admin.management.converters', compact('converters', 'statuses', 'cities'));
    }

    /**
     * Show all machine dealers
     */
    public function machineDealers(Request $request)
    {
        $query = MachineDealer::with('user', 'machineListings');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person_name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Profile complete filter
        if ($request->filled('profile_complete')) {
            $query->where('profile_complete', $request->profile_complete === 'yes');
        }

        // City filter
        if ($request->filled('city')) {
            $query->where('city', 'like', "%{$request->city}%");
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $machineDealers = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $statuses = ['PENDING', 'ACTIVE', 'INACTIVE'];
        $cities = MachineDealer::whereNotNull('city')->distinct()->pluck('city')->sort();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.machine-dealers-table', compact('machineDealers'))->render(),
                'total' => $machineDealers->total(),
                'firstItem' => $machineDealers->firstItem(),
                'lastItem' => $machineDealers->lastItem()
            ]);
        }
        
        return view('admin.management.machine-dealers', compact('machineDealers', 'statuses', 'cities'));
    }

    /**
     * Show all inquiries
     */
    public function inquiries(Request $request)
    {
        $query = Inquiry::with('brand', 'materials', 'machines');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('brand', function($bq) use ($search) {
                      $bq->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('inquiry_type', strtoupper($request->type));
        }

        // Intent filter
        if ($request->filled('intent')) {
            $query->where('intent', strtoupper($request->intent));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Urgency filter
        if ($request->filled('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        // Location filter
        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $inquiries = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $types = ['MATERIAL', 'MACHINE', 'JOB'];
        $intents = ['BUY', 'SELL'];
        $statuses = ['DRAFT', 'MATCHING', 'SESSION_LOCKED', 'DEAL_WON', 'DEAL_LOST', 'SESSION_EXPIRED', 'BRAND_CANCELLED'];
        $urgencies = ['normal', 'urgent'];
        $locations = Inquiry::whereNotNull('location')->distinct()->pluck('location')->sort();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.inquiries-table', compact('inquiries'))->render(),
                'total' => $inquiries->total(),
                'firstItem' => $inquiries->firstItem(),
                'lastItem' => $inquiries->lastItem()
            ]);
        }
        
        return view('admin.management.inquiries', compact('inquiries', 'types', 'intents', 'statuses', 'urgencies', 'locations'));
    }

    /**
     * Show inquiry detail
     */
    public function inquiryDetail(Inquiry $inquiry)
    {
        $inquiry->load([
            'brand',
            'materials',
            'machines',
            'machineListing',
            'session',
            'quotations',
            'responses',
            'acceptances'
        ]);
        
        // Try to load poster if available
        if ($inquiry->poster_id && $inquiry->poster_type) {
            try {
                $inquiry->load('poster');
            } catch (\Exception $e) {
                // Poster might not be loadable, continue without it
            }
        }
        
        // Calculate statistics
        $totalQuotations = $inquiry->quotations->count();
        $totalResponses = $inquiry->responses->count();
        $totalAcceptances = $inquiry->acceptances->count();
        
        return view('admin.management.inquiry-detail', compact(
            'inquiry',
            'totalQuotations',
            'totalResponses',
            'totalAcceptances'
        ));
    }

    /**
     * Show material inquiries
     */
    public function materialInquiries(Request $request)
    {
        $query = Inquiry::with('brand', 'materials', 'machines')
            ->where('inquiry_type', 'MATERIAL');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('brand', function($bq) use ($search) {
                      $bq->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Intent filter
        if ($request->filled('intent')) {
            $query->where('intent', strtoupper($request->intent));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Urgency filter
        if ($request->filled('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        // Location filter
        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $inquiries = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $types = ['MATERIAL'];
        $intents = ['BUY', 'SELL'];
        $statuses = ['DRAFT', 'MATCHING', 'SESSION_LOCKED', 'DEAL_WON', 'DEAL_LOST', 'SESSION_EXPIRED', 'BRAND_CANCELLED'];
        $urgencies = ['normal', 'urgent'];
        $locations = Inquiry::whereNotNull('location')->distinct()->pluck('location')->sort();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.inquiries-table', compact('inquiries'))->render(),
                'total' => $inquiries->total(),
                'firstItem' => $inquiries->firstItem(),
                'lastItem' => $inquiries->lastItem()
            ]);
        }
        
        return view('admin.management.inquiries', compact('inquiries', 'types', 'intents', 'statuses', 'urgencies', 'locations'));
    }

    /**
     * Show machine inquiries
     */
    public function machineInquiries(Request $request)
    {
        $query = Inquiry::with('brand', 'materials', 'machines')
            ->where('inquiry_type', 'MACHINE');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('brand', function($bq) use ($search) {
                      $bq->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Intent filter
        if ($request->filled('intent')) {
            $query->where('intent', strtoupper($request->intent));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Urgency filter
        if ($request->filled('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        // Location filter
        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $inquiries = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $types = ['MACHINE'];
        $intents = ['BUY', 'SELL'];
        $statuses = ['DRAFT', 'MATCHING', 'SESSION_LOCKED', 'DEAL_WON', 'DEAL_LOST', 'SESSION_EXPIRED', 'BRAND_CANCELLED'];
        $urgencies = ['normal', 'urgent'];
        $locations = Inquiry::whereNotNull('location')->distinct()->pluck('location')->sort();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.inquiries-table', compact('inquiries'))->render(),
                'total' => $inquiries->total(),
                'firstItem' => $inquiries->firstItem(),
                'lastItem' => $inquiries->lastItem()
            ]);
        }
        
        return view('admin.management.inquiries', compact('inquiries', 'types', 'intents', 'statuses', 'urgencies', 'locations'));
    }

    /**
     * Show job inquiries
     */
    public function jobInquiries(Request $request)
    {
        $query = Inquiry::with('brand', 'materials', 'machines')
            ->where('inquiry_type', 'JOB');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('brand', function($bq) use ($search) {
                      $bq->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Intent filter
        if ($request->filled('intent')) {
            $query->where('intent', strtoupper($request->intent));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Urgency filter
        if ($request->filled('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        // Location filter
        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $inquiries = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $types = ['JOB'];
        $intents = ['BUY', 'SELL'];
        $statuses = ['DRAFT', 'MATCHING', 'SESSION_LOCKED', 'DEAL_WON', 'DEAL_LOST', 'SESSION_EXPIRED', 'BRAND_CANCELLED'];
        $urgencies = ['normal', 'urgent'];
        $locations = Inquiry::whereNotNull('location')->distinct()->pluck('location')->sort();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.inquiries-table', compact('inquiries'))->render(),
                'total' => $inquiries->total(),
                'firstItem' => $inquiries->firstItem(),
                'lastItem' => $inquiries->lastItem()
            ]);
        }
        
        return view('admin.management.inquiries', compact('inquiries', 'types', 'intents', 'statuses', 'urgencies', 'locations'));
    }

    /**
     * Show all sessions
     */
    public function allSessions()
    {
        $sessions = MatchingSession::with('inquiry', 'acceptances.dealer')
            ->latest()
            ->paginate(20);
        
        return view('admin.management.sessions', compact('sessions'));
    }

    /**
     * Show active sessions
     */
    public function activeSessions()
    {
        $sessions = MatchingSession::with('inquiry', 'acceptances.dealer')
            ->where('status', 'ACTIVE')
            ->latest()
            ->paginate(20);
        
        return view('admin.management.sessions', compact('sessions'));
    }

    /**
     * Show completed sessions
     */
    public function completedSessions()
    {
        $sessions = MatchingSession::with('inquiry', 'acceptances.dealer')
            ->where('status', 'COMPLETED')
            ->latest()
            ->paginate(20);
        
        return view('admin.management.sessions', compact('sessions'));
    }

    /**
     * Show all materials
     */
    public function materials(Request $request)
    {
        $query = Material::with('finishes');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $materials = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $categories = Material::whereNotNull('category')->distinct()->pluck('category')->sort();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.materials-table', compact('materials'))->render(),
                'total' => $materials->total(),
                'firstItem' => $materials->firstItem(),
                'lastItem' => $materials->lastItem()
            ]);
        }
        
        return view('admin.management.materials', compact('materials', 'categories'));
    }

    /**
     * Store new material
     */
    public function storeMaterial(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:materials,name',
            'category' => 'nullable|string|max:255',
        ]);

        Material::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Material created successfully!'
            ]);
        }

        return redirect()->route('admin.reference.materials')
            ->with('success', 'Material created successfully!');
    }

    /**
     * Update material
     */
    public function updateMaterial(Request $request, Material $material)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:materials,name,' . $material->id,
            'category' => 'nullable|string|max:255',
        ]);

        $material->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Material updated successfully!'
            ]);
        }

        return redirect()->route('admin.reference.materials')
            ->with('success', 'Material updated successfully!');
    }

    /**
     * Delete material
     */
    public function deleteMaterial(Request $request, Material $material)
    {
        $material->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Material deleted successfully!'
            ]);
        }

        return redirect()->route('admin.reference.materials')
            ->with('success', 'Material deleted successfully!');
    }

    /**
     * Show all machines
     */
    public function machines(Request $request)
    {
        $query = Machine::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $machines = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $types = Machine::whereNotNull('type')->distinct()->pluck('type')->sort();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.machines-table', compact('machines'))->render(),
                'total' => $machines->total(),
                'firstItem' => $machines->firstItem(),
                'lastItem' => $machines->lastItem()
            ]);
        }
        
        return view('admin.management.machines', compact('machines', 'types'));
    }

    /**
     * Store new machine
     */
    public function storeMachine(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Machine::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Machine created successfully!'
            ]);
        }

        return redirect()->route('admin.reference.machines')
            ->with('success', 'Machine created successfully!');
    }

    /**
     * Update machine
     */
    public function updateMachine(Request $request, Machine $machine)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $machine->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Machine updated successfully!'
            ]);
        }

        return redirect()->route('admin.reference.machines')
            ->with('success', 'Machine updated successfully!');
    }

    /**
     * Delete machine
     */
    public function deleteMachine(Request $request, Machine $machine)
    {
        $machine->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Machine deleted successfully!'
            ]);
        }

        return redirect()->route('admin.reference.machines')
            ->with('success', 'Machine deleted successfully!');
    }

    /**
     * Show reference brands (mill brands)
     */
    public function referenceBrands(Request $request)
    {
        $query = Brand::whereNotNull('name')->whereNull('user_id');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $brands = $query->latest()->paginate(20)->withQueryString();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.reference-brands-table', compact('brands'))->render(),
                'total' => $brands->total(),
                'firstItem' => $brands->firstItem(),
                'lastItem' => $brands->lastItem()
            ]);
        }
        
        return view('admin.management.reference-brands', compact('brands'));
    }

    /**
     * Store new reference brand
     */
    public function storeReferenceBrand(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
        ]);

        Brand::create([
            'name' => $validated['name'],
            'user_id' => null, // Mill brand
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Reference brand created successfully!'
            ]);
        }

        return redirect()->route('admin.reference.brands')
            ->with('success', 'Reference brand created successfully!');
    }

    /**
     * Update reference brand
     */
    public function updateReferenceBrand(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $brand->id,
        ]);

        $brand->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Reference brand updated successfully!'
            ]);
        }

        return redirect()->route('admin.reference.brands')
            ->with('success', 'Reference brand updated successfully!');
    }

    /**
     * Delete reference brand
     */
    public function deleteReferenceBrand(Request $request, Brand $brand)
    {
        $brand->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Reference brand deleted successfully!'
            ]);
        }

        return redirect()->route('admin.reference.brands')
            ->with('success', 'Reference brand deleted successfully!');
    }

    /**
     * Show all finishes
     */
    public function finishes(Request $request)
    {
        $query = MaterialFinish::with('material');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhereHas('material', function($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Material filter
        if ($request->filled('material_id')) {
            $query->where('material_id', $request->material_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $finishes = $query->latest()->paginate(20)->withQueryString();
        
        // Get unique values for filter dropdowns
        $types = MaterialFinish::whereNotNull('type')->distinct()->pluck('type')->sort();
        $materials = Material::orderBy('name')->get();
        
        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.finishes-table', compact('finishes'))->render(),
                'total' => $finishes->total(),
                'firstItem' => $finishes->firstItem(),
                'lastItem' => $finishes->lastItem()
            ]);
        }
        
        return view('admin.management.finishes', compact('finishes', 'types', 'materials'));
    }

    /**
     * Store new finish
     */
    public function storeFinish(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'material_id' => 'nullable|exists:materials,id',
        ]);

        MaterialFinish::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Finish created successfully!'
            ]);
        }

        return redirect()->route('admin.reference.finishes')
            ->with('success', 'Finish created successfully!');
    }

    /**
     * Update finish
     */
    public function updateFinish(Request $request, MaterialFinish $finish)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'material_id' => 'nullable|exists:materials,id',
        ]);

        $finish->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Finish updated successfully!'
            ]);
        }

        return redirect()->route('admin.reference.finishes')
            ->with('success', 'Finish updated successfully!');
    }

    /**
     * Delete finish
     */
    public function deleteFinish(Request $request, MaterialFinish $finish)
    {
        $finish->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Finish deleted successfully!'
            ]);
        }

        return redirect()->route('admin.reference.finishes')
            ->with('success', 'Finish deleted successfully!');
    }

    /**
     * Show Terms & Conditions
     */
    public function terms()
    {
        $terms = \DB::table('cms_content')->where('type', 'terms')->first();
        return view('admin.management.cms.terms', compact('terms'));
    }

    /**
     * Store/Update Terms & Conditions
     */
    public function storeTerms(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        \DB::table('cms_content')->updateOrInsert(
            ['type' => 'terms'],
            ['content' => $validated['content'], 'updated_at' => now()]
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Terms & Conditions updated successfully!'
            ]);
        }

        return redirect()->route('admin.cms.terms')
            ->with('success', 'Terms & Conditions updated successfully!');
    }

    /**
     * Update Terms & Conditions
     */
    public function updateTerms(Request $request, $id)
    {
        return $this->storeTerms($request);
    }

    /**
     * Show Privacy Policy
     */
    public function privacy()
    {
        $privacy = \DB::table('cms_content')->where('type', 'privacy')->first();
        return view('admin.management.cms.privacy', compact('privacy'));
    }

    /**
     * Store/Update Privacy Policy
     */
    public function storePrivacy(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        \DB::table('cms_content')->updateOrInsert(
            ['type' => 'privacy'],
            ['content' => $validated['content'], 'updated_at' => now()]
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Privacy Policy updated successfully!'
            ]);
        }

        return redirect()->route('admin.cms.privacy')
            ->with('success', 'Privacy Policy updated successfully!');
    }

    /**
     * Update Privacy Policy
     */
    public function updatePrivacy(Request $request, $id)
    {
        return $this->storePrivacy($request);
    }

    /**
     * Show FAQ
     */
    public function faq(Request $request)
    {
        $query = \DB::table('faqs');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $query->orderBy('sort_order')->orderBy('id');
        $faqs = $query->paginate(20)->withQueryString();

        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.cms.faq-table', compact('faqs'))->render(),
                'total' => $faqs->total(),
                'firstItem' => $faqs->firstItem(),
                'lastItem' => $faqs->lastItem()
            ]);
        }

        return view('admin.management.cms.faq', compact('faqs'));
    }

    /**
     * Store FAQ
     */
    public function storeFaq(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        \DB::table('faqs')->insert([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'FAQ created successfully!'
            ]);
        }

        return redirect()->route('admin.cms.faq')
            ->with('success', 'FAQ created successfully!');
    }

    /**
     * Update FAQ
     */
    public function updateFaq(Request $request, $id)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        \DB::table('faqs')->where('id', $id)->update([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'updated_at' => now(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'FAQ updated successfully!'
            ]);
        }

        return redirect()->route('admin.cms.faq')
            ->with('success', 'FAQ updated successfully!');
    }

    /**
     * Delete FAQ
     */
    public function deleteFaq(Request $request, $id)
    {
        \DB::table('faqs')->where('id', $id)->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'FAQ deleted successfully!'
            ]);
        }

        return redirect()->route('admin.cms.faq')
            ->with('success', 'FAQ deleted successfully!');
    }

    /**
     * Show Corporate
     */
    public function corporate(Request $request)
    {
        $query = \DB::table('corporate_content')->orderBy('sort_order')->orderBy('id');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $corporate = $query->paginate(20)->withQueryString();

        // If AJAX request, return only table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.management.cms.corporate-table', compact('corporate'))->render(),
                'total' => $corporate->total(),
                'firstItem' => $corporate->firstItem(),
                'lastItem' => $corporate->lastItem()
            ]);
        }

        return view('admin.management.cms.corporate', compact('corporate'));
    }

    /**
     * Store Corporate
     */
    public function storeCorporate(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        \DB::table('corporate_content')->insert([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Corporate content created successfully!'
            ]);
        }

        return redirect()->route('admin.cms.corporate')
            ->with('success', 'Corporate content created successfully!');
    }

    /**
     * Update Corporate
     */
    public function updateCorporate(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        \DB::table('corporate_content')->where('id', $id)->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'updated_at' => now(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Corporate content updated successfully!'
            ]);
        }

        return redirect()->route('admin.cms.corporate')
            ->with('success', 'Corporate content updated successfully!');
    }

    /**
     * Delete Corporate
     */
    public function deleteCorporate(Request $request, $id)
    {
        \DB::table('corporate_content')->where('id', $id)->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Corporate content deleted successfully!'
            ]);
        }

        return redirect()->route('admin.cms.corporate')
            ->with('success', 'Corporate content deleted successfully!');
    }
}
