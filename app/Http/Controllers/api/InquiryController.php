<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInquiryRequest;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Enums\InquiryStatus;
use App\Services\MatchmakingService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Gate;

class InquiryController extends Controller
{
    public function __construct(
        protected MatchmakingService $matchmakingService
    ) {
    }

    /**
     * Create a new inquiry (DRAFT status)
     */
    public function store(StoreInquiryRequest $request)
    {
        try {
            Gate::authorize('create', Inquiry::class);
            
            $user = $request->user();
            $data = $request->validated();
            
            DB::beginTransaction();
            
            // Determine poster
            $poster = $user->brand ?? $user->converter;
            if (!$poster) {
                return Response::error('Only brands or converters can create inquiries', null, HttpResponse::HTTP_FORBIDDEN);
            }
            
            // Create inquiry
            $inquiry = Inquiry::create([
                'poster_id' => $poster->id,
                'poster_type' => $user->brand ? 'brand' : 'converter',
                'brand_id' => $user->brand?->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => InquiryStatus::DRAFT,
                'urgency' => $data['urgency'] ?? 'normal',
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'timeline' => $data['timeline'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'is_visible_to_brand' => true,
                'is_visible_to_dealers' => false,
                'hide_brand_identity' => true,
                'hide_exact_location' => true,
            ]);
            
            // Create inquiry items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    InquiryItem::create([
                        'inquiry_id' => $inquiry->id,
                        'material_id' => $itemData['material_id'] ?? null,
                        'material_category' => $itemData['material_category'] ?? null,
                        'finish_coating' => $itemData['finish_coating'] ?? null,
                        'thickness_gsm' => $itemData['thickness_gsm'] ?? null,
                        'thickness_mm' => $itemData['thickness_mm'] ?? null,
                        'thickness_unit' => $itemData['thickness_unit'] ?? 'gsm',
                        'thickness_tolerance_percent' => $data['urgency'] === 'urgent' ? 10.0 : 5.0,
                        'thickness_tolerance_absolute' => $data['urgency'] === 'urgent' ? 0.3 : 0.2,
                        'quantity' => $itemData['quantity'],
                        'quantity_unit' => $itemData['quantity_unit'],
                        'additional_specs' => $itemData['additional_specs'] ?? null,
                    ]);
                }
            }
            
            DB::commit();
            
            $inquiry->load('items');
            
            return Response::success('Inquiry created successfully', $inquiry, null, HttpResponse::HTTP_CREATED);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Post inquiry (trigger matchmaking)
     */
    public function post(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('post', $inquiry);
            
            $user = $request->user();
            
            // Check wallet balance (posting fee)
            $postingFee = $inquiry->urgency === 'urgent' ? 70 : 50; // Credits
            $wallet = $user->wallet;
            
            if (!$wallet || $wallet->balance < $postingFee) {
                return Response::error('Insufficient wallet balance', null, HttpResponse::HTTP_PAYMENT_REQUIRED);
            }
            
            DB::beginTransaction();
            
            // Deduct posting fee
            $wallet->deductCredits($postingFee, 'Post requirement fee', 'inquiry', $inquiry->id);
            
            // Update inquiry status
            $inquiry->update([
                'status' => InquiryStatus::POSTED,
                'posted_at' => now(),
                'posting_fee_paid' => true,
                'posting_fee_amount' => $postingFee,
            ]);
            
            // Create or update session
            $session = $inquiry->session()->firstOrCreate([
                'inquiry_id' => $inquiry->id,
            ], [
                'status' => \App\Enums\SessionStatus::POSTED,
                'posted_at' => now(),
                'is_visible_to_brand' => true,
                'is_visible_to_dealers' => false,
            ]);
            
            // Trigger matchmaking
            $matchedDealerIds = $this->matchmakingService->findMatchingDealers($inquiry, 10);
            
            // Update session status
            $session->update([
                'status' => \App\Enums\SessionStatus::MATCHING,
                'matching_started_at' => now(),
                'is_visible_to_dealers' => true,
            ]);
            
            // Notify matched dealers
            $this->matchmakingService->notifyMatchedDealers($inquiry, $matchedDealerIds);
            
            DB::commit();
            
            $inquiry->refresh();
            $inquiry->load(['session', 'items']);
            
            return Response::success('Inquiry posted successfully', [
                'inquiry' => $inquiry,
                'matched_dealers_count' => count($matchedDealerIds),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get inquiry details
     */
    public function show(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('view', $inquiry);
            
            $user = $request->user();
            
            // Load relationships
            $inquiry->load(['items', 'materials', 'session', 'responses']);
            
            // For dealers, return sanitized view
            if ($user->dealer) {
                $data = $inquiry->getDealerViewData();
            } else {
                $data = $inquiry->toArray();
            }
            
            return Response::success('Inquiry retrieved successfully', $data);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get inquiries for dealer (matched only)
     */
    public function dealerInquiries(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->dealer) {
                return Response::error('Only dealers can access this endpoint', null, HttpResponse::HTTP_FORBIDDEN);
            }
            
            $dealerId = $user->dealer->id;
            
            // Get inquiries visible to this dealer
            $inquiries = Inquiry::visibleToDealer($dealerId)
                ->with(['items', 'materials'])
                ->when($request->status, function ($q, $status) {
                    $q->where('status', $status);
                })
                ->when($request->material_category, function ($q, $category) {
                    $q->whereHas('items', function ($query) use ($category) {
                        $query->where('material_category', $category);
                    });
                })
                ->orderBy('posted_at', 'desc')
                ->paginate($request->per_page ?? 15);
            
            // Sanitize for dealer view
            $inquiries->getCollection()->transform(function ($inquiry) {
                return $inquiry->getDealerViewData();
            });
            
            return Response::success('Inquiries retrieved successfully', $inquiries);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get responses for an inquiry (brand/converter only)
     */
    public function responses(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('viewResponses', $inquiry);
            
            $responses = $inquiry->responses()
                ->with(['responder.dealer'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Anonymize dealer info until lock
            if ($inquiry->status !== InquiryStatus::LOCKED && $inquiry->status !== InquiryStatus::CHAT_ACTIVE) {
                $responses->transform(function ($response) {
                    if ($response->responder && $response->responder->dealer) {
                        $dealer = $response->responder->dealer;
                        $response->responder->dealer = [
                            'id' => null,
                            'company_name' => null,
                            'location' => $dealer->locations->first()?->city ?? 'Unknown',
                        ];
                    }
                    return $response;
                });
            }
            
            return Response::success('Responses retrieved successfully', $responses);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Republish inquiry
     */
    public function republish(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('republish', $inquiry);
            
            DB::beginTransaction();
            
            // Create new inquiry based on old one
            $newInquiry = $inquiry->replicate();
            $newInquiry->status = InquiryStatus::REPUBLISHED;
            $newInquiry->posted_at = null;
            $newInquiry->locked_at = null;
            $newInquiry->expires_at = null;
            $newInquiry->is_visible_to_dealers = false;
            $newInquiry->republish_count = $inquiry->republish_count + 1;
            $newInquiry->last_republished_at = now();
            $newInquiry->save();
            
            // Copy items
            foreach ($inquiry->items as $item) {
                $newItem = $item->replicate();
                $newItem->inquiry_id = $newInquiry->id;
                $newItem->save();
            }
            
            // Update old inquiry
            $inquiry->update([
                'cooldown_until' => now()->addDays(7), // 7-day cooldown
            ]);
            
            DB::commit();
            
            return Response::success('Inquiry republished successfully', $newInquiry);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Save inquiry step (multi-step creation)
     * Screen: Post Requirement Screen (Step 2 of 9)
     */
    public function saveStep(\App\Http\Requests\SaveInquiryStepRequest $request)
    {
        try {
            Gate::authorize('create', Inquiry::class);
            
            $user = $request->user();
            $data = $request->validated();
            $step = $data['step'];
            
            DB::beginTransaction();
            
            $poster = $user->brand ?? $user->converter;
            if (!$poster) {
                return Response::error('Only brands or converters can create inquiries', null, HttpResponse::HTTP_FORBIDDEN);
            }
            
            // Get or create inquiry
            if (isset($data['inquiry_id']) && $data['inquiry_id']) {
                $inquiry = Inquiry::where('id', $data['inquiry_id'])
                    ->where('poster_id', $poster->id)
                    ->where('poster_type', $user->brand ? 'brand' : 'converter')
                    ->where('status', InquiryStatus::DRAFT)
                    ->firstOrFail();
            } else {
                // Create new inquiry
                $inquiry = Inquiry::create([
                    'poster_id' => $poster->id,
                    'poster_type' => $user->brand ? 'brand' : 'converter',
                    'brand_id' => $user->brand?->id,
                    'status' => InquiryStatus::DRAFT,
                    'is_visible_to_brand' => true,
                    'is_visible_to_dealers' => false,
                    'hide_brand_identity' => true,
                    'hide_exact_location' => true,
                ]);
            }
            
            // Step 2: Technical Specifications
            if ($step == 2) {
                // Update inquiry
                $inquiry->update([
                    'packaging_type' => $data['packaging_type'] ?? null,
                    'thickness' => $data['thickness_gsm'] ?? $data['thickness_mm'] ?? null,
                    'thickness_unit' => $data['thickness_unit'] ?? 'gsm',
                    'size' => $data['size'] ?? null,
                    'quantity' => $data['quantity'] ?? null,
                    'quantity_unit' => $data['quantity_unit'] ?? null,
                    'urgency' => $data['urgency'] ?? 'normal',
                    'location' => $data['location'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                ]);
                
                // Create or update inquiry item
                $item = $inquiry->items()->firstOrCreate(
                    ['inquiry_id' => $inquiry->id],
                    [
                        'material_category' => $data['material_category'] ?? null,
                        'thickness_gsm' => $data['thickness_gsm'] ?? null,
                        'thickness_mm' => $data['thickness_mm'] ?? null,
                        'thickness_unit' => $data['thickness_unit'] ?? 'gsm',
                        'quantity' => $data['quantity'],
                        'quantity_unit' => $data['quantity_unit'],
                        'thickness_tolerance_percent' => ($data['urgency'] ?? 'normal') === 'urgent' ? 10.0 : 5.0,
                        'thickness_tolerance_absolute' => ($data['urgency'] ?? 'normal') === 'urgent' ? 0.3 : 0.2,
                    ]
                );
                
                if ($item->wasRecentlyCreated === false) {
                    $item->update([
                        'material_category' => $data['material_category'] ?? null,
                        'thickness_gsm' => $data['thickness_gsm'] ?? null,
                        'thickness_mm' => $data['thickness_mm'] ?? null,
                        'thickness_unit' => $data['thickness_unit'] ?? 'gsm',
                        'quantity' => $data['quantity'],
                        'quantity_unit' => $data['quantity_unit'],
                    ]);
                }
            }
            
            DB::commit();
            
            $inquiry->load('items');
            
            return Response::success('Step saved successfully', [
                'inquiry_id' => $inquiry->id,
                'step' => $step,
                'inquiry' => $inquiry,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Calculate posting fee before posting
     * Screen: Payment Confirmation Screen
     */
    public function calculatePostingFee(\App\Http\Requests\CalculatePostingFeeRequest $request)
    {
        try {
            $user = $request->user();
            $inquiry = Inquiry::findOrFail($request->inquiry_id);
            
            Gate::authorize('post', $inquiry);
            
            // Calculate fees
            $standardFee = 50; // Credits
            $urgencyAddon = $inquiry->urgency === 'urgent' ? 20 : 0;
            $totalFee = $standardFee + $urgencyAddon;
            
            // Get wallet balance
            $wallet = $user->wallet;
            $availableBalance = $wallet ? $wallet->balance : 0;
            $sufficientCredits = $availableBalance >= $totalFee;
            
            return Response::success('Posting fee calculated', [
                'standard_fee' => $standardFee,
                'urgency_addon' => $urgencyAddon,
                'total_fee' => $totalFee,
                'wallet_balance' => $availableBalance,
                'sufficient_credits' => $sufficientCredits,
                'inquiry' => [
                    'id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'urgency' => $inquiry->urgency,
                ],
            ]);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get posting success details with matchmaking status
     * Screen: Posting Success Screen
     */
    public function getPostingStatus(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('view', $inquiry);
            
            $session = $inquiry->session;
            $matchmakingLogs = $inquiry->matchmakingLogs()->where('is_visible', true)->get();
            
            // Calculate matchmaking progress
            $totalDealersScanned = \App\Models\Dealer::where('status', 'active')->count();
            $matchedDealers = $matchmakingLogs->count();
            $progressPercent = $totalDealersScanned > 0 ? ($matchedDealers / $totalDealersScanned) * 100 : 0;
            
            return Response::success('Posting status retrieved', [
                'inquiry' => [
                    'id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'status' => $inquiry->status->value,
                    'posted_at' => $inquiry->posted_at,
                    'items' => $inquiry->items->map(function ($item) {
                        return [
                            'material_category' => $item->material_category,
                            'quantity' => $item->quantity,
                            'quantity_unit' => $item->quantity_unit,
                        ];
                    }),
                ],
                'matchmaking' => [
                    'status' => $session ? $session->status->value : 'FINDING',
                    'matched_dealers_count' => $matchedDealers,
                    'total_dealers_scanned' => $totalDealersScanned,
                    'progress_percent' => round($progressPercent, 1),
                    'is_finding_matches' => $session && $session->status === \App\Enums\SessionStatus::MATCHING,
                ],
                'session_id' => $session ? $session->id : null,
            ]);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get matchmaking responses with filters
     * Screen: Matchmaking Responses Screen
     */
    public function getMatchmakingResponses(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('viewResponses', $inquiry);
            
            $filter = $request->input('filter', 'all'); // all, exact_match, slight_variation, nearest
            $responses = $inquiry->responses()
                ->with(['responder.dealer.locations'])
                ->get();
            
            // Get matchmaking logs for scoring
            $matchmakingLogs = $inquiry->matchmakingLogs()
                ->whereIn('dealer_id', $responses->pluck('responder.dealer.id')->filter())
                ->get()
                ->keyBy('dealer_id');
            
            // Enhance responses with matchmaking data
            $enhancedResponses = $responses->map(function ($response) use ($matchmakingLogs, $inquiry) {
                $dealer = $response->responder->dealer ?? null;
                $log = $dealer ? $matchmakingLogs->get($dealer->id) : null;
                
                // Calculate distance
                $distance = null;
                if ($inquiry->latitude && $inquiry->longitude && $dealer) {
                    $dealerLocation = $dealer->locations->first();
                    if ($dealerLocation && $dealerLocation->latitude && $dealerLocation->longitude) {
                        $distance = $this->calculateDistance(
                            $inquiry->latitude,
                            $inquiry->longitude,
                            $dealerLocation->latitude,
                            $dealerLocation->longitude
                        );
                    }
                }
                
                // Determine match type
                $matchType = 'exact_match';
                if ($log) {
                    if (!$log->material_match || !$log->thickness_match) {
                        $matchType = 'slight_variation';
                    }
                }
                
                return [
                    'id' => $response->id,
                    'match_type' => $matchType,
                    'distance_km' => $distance,
                    'dealer' => [
                        'id' => $inquiry->status === InquiryStatus::LOCKED ? ($dealer->id ?? null) : null,
                        'company_name' => $inquiry->status === InquiryStatus::LOCKED ? ($dealer->company_name ?? null) : null,
                        'location' => $dealer ? ($dealer->locations->first()?->city . ', ' . $dealer->locations->first()?->state) : 'Unknown',
                    ],
                    'quantity_offered' => $response->quantity_offered,
                    'quoted_price' => $response->quoted_price,
                    'price_status' => $response->price_status,
                    'additional_details' => $response->additional_details,
                    'responded_at' => $response->created_at,
                    'is_shortlisted' => $response->session && $response->session->participants()
                        ->where('participant_type', 'dealer')
                        ->where('participant_id', $dealer->id ?? 0)
                        ->where('is_selected', true)
                        ->exists(),
                ];
            });
            
            // Apply filters
            if ($filter === 'exact_match') {
                $enhancedResponses = $enhancedResponses->where('match_type', 'exact_match');
            } elseif ($filter === 'slight_variation') {
                $enhancedResponses = $enhancedResponses->where('match_type', 'slight_variation');
            } elseif ($filter === 'nearest') {
                $enhancedResponses = $enhancedResponses->sortBy('distance_km');
            }
            
            // Sort by distance if nearest filter
            if ($filter === 'nearest') {
                $enhancedResponses = $enhancedResponses->values();
            } else {
                $enhancedResponses = $enhancedResponses->sortByDesc('responded_at')->values();
            }
            
            // Get countdown if session exists
            $countdown = null;
            $session = $inquiry->session;
            if ($session && $session->expires_at) {
                $secondsLeft = max(0, now()->diffInSeconds($session->expires_at, false));
                $countdown = [
                    'hours' => floor($secondsLeft / 3600),
                    'minutes' => floor(($secondsLeft % 3600) / 60),
                    'seconds' => $secondsLeft % 60,
                ];
            }
            
            return Response::success('Matchmaking responses retrieved', [
                'inquiry' => [
                    'id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'items' => $inquiry->items->map(function ($item) {
                        return [
                            'material_category' => $item->material_category,
                            'quantity' => $item->quantity,
                            'quantity_unit' => $item->quantity_unit,
                        ];
                    }),
                ],
                'countdown' => $countdown,
                'responses_count' => $enhancedResponses->count(),
                'responses' => $enhancedResponses,
                'filter' => $filter,
            ]);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Shortlist or reject a response
     * Screen: Matchmaking Responses Screen (Shortlist/Reject buttons)
     */
    public function shortlistResponse(\App\Http\Requests\ShortlistResponseRequest $request, \App\Models\Response $response)
    {
        try {
            $inquiry = $response->inquiry;
            Gate::authorize('viewResponses', $inquiry);
            
            // Can only shortlist if session is not locked yet
            if ($inquiry->status === InquiryStatus::LOCKED) {
                return Response::error('Session already locked', null, HttpResponse::HTTP_BAD_REQUEST);
            }
            
            $action = $request->validated()['action'];
            $dealer = $response->responder->dealer;
            
            if (!$dealer) {
                return Response::error('Dealer not found', null, HttpResponse::HTTP_NOT_FOUND);
            }
            
            // Update matchmaking log
            $matchmakingLog = $inquiry->matchmakingLogs()
                ->where('dealer_id', $dealer->id)
                ->first();
            
            if ($matchmakingLog) {
                if ($action === 'shortlist') {
                    $matchmakingLog->update(['is_selected' => true, 'selected_at' => now()]);
                } else {
                    $matchmakingLog->update(['is_selected' => false]);
                }
            }
            
            return Response::success("Response {$action}ed successfully", [
                'response_id' => $response->id,
                'action' => $action,
                'is_shortlisted' => $action === 'shortlist',
            ]);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Helper: Calculate distance between two coordinates
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return round($earthRadius * $c, 1);
    }
}
