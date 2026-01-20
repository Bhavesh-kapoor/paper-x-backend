<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\SessionService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class SessionController extends Controller
{
    public function __construct(
        protected SessionService $sessionService
    ) {
    }

    public function getSession(int $sessionId)
    {
        try {
            $user = request()->user();
            $session = \App\Models\MatchingSession::with(['inquiry.items', 'participants.participant', 'chatThread'])
                ->findOrFail($sessionId);
            
            \Illuminate\Support\Facades\Gate::authorize('view', $session);
            
            $inquiry = $session->inquiry;
            
            // Get selected partners (for locked session)
            $selectedPartners = [];
            if ($session->status === \App\Enums\SessionStatus::LOCKED || $session->status === \App\Enums\SessionStatus::CHAT_ACTIVE) {
                $selectedPartners = $session->participants()
                    ->where('is_selected', true)
                    ->where('role', 'responder')
                    ->with('participant')
                    ->get()
                    ->map(function ($participant) {
                        $dealer = $participant->participant;
                        if (!$dealer || !($dealer instanceof \App\Models\Dealer)) {
                            return null;
                        }
                        $user = $dealer->user;
                        return [
                            'id' => $dealer->id,
                            'company_name' => $user->company_name ?? $user->name ?? 'Unknown',
                            'location' => $dealer->locations->first()?->city ?? $user->city ?? 'Unknown',
                        ];
                    })
                    ->filter();
            }
            
            // Format session data
            $sessionData = [
                'id' => $session->id,
                'project_id' => 'PRJ-' . str_pad($session->id, 4, '0', STR_PAD_LEFT),
                'status' => $session->status->value,
                'inquiry' => [
                    'id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'items' => $inquiry->items,
                ],
                'selected_partners_count' => count($selectedPartners),
                'selected_partners' => $selectedPartners,
                'chat_enabled' => $session->chat_enabled,
                'chat_thread_id' => $session->chatThread?->id,
                'locked_at' => $session->locked_at,
            ];
            
            return Response::success('Session details retrieved', $sessionData);
            
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get session history
     * Screen: Session History Screen
     */
    public function getHistory()
    {
        try {
            $user = request()->user();
            $filter = request()->input('filter', 'all'); // all, completed, expired
            $search = request()->input('search');
            
            $query = \App\Models\MatchingSession::query()
                ->when($user->brand || $user->converter, function ($q) use ($user) {
                    $q->whereHas('inquiry', function ($query) use ($user) {
                        $query->where('poster_id', $user->brand?->id ?? $user->converter?->id)
                            ->where('poster_type', $user->brand ? 'brand' : 'converter');
                    });
                })
                ->when($user->dealer, function ($q) use ($user) {
                    $q->visibleToDealer($user->dealer->id);
                })
                ->with(['inquiry.items', 'participants']);
            
            // Apply status filter
            if ($filter === 'completed') {
                $query->whereIn('status', [
                    \App\Enums\SessionStatus::DEAL_SUCCESS,
                    \App\Enums\SessionStatus::DEAL_FAILED,
                ]);
            } elseif ($filter === 'expired') {
                $query->where('status', \App\Enums\SessionStatus::EXPIRED);
            }
            
            // Search filter
            if ($search) {
                $query->whereHas('inquiry', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            $sessions = $query->orderBy('created_at', 'desc')
                ->paginate(request()->per_page ?? 15);
            
            // Group by month
            $groupedSessions = $sessions->getCollection()->groupBy(function ($session) {
                return $session->created_at->format('F Y');
            });
            
            // Transform sessions
            $transformedSessions = $groupedSessions->map(function ($monthSessions, $month) {
                return [
                    'month' => $month,
                    'sessions' => $monthSessions->map(function ($session) {
                        $inquiry = $session->inquiry;
                        $selectedPartnersCount = $session->participants()
                            ->where('is_selected', true)
                            ->where('role', 'responder')
                            ->count();
                        
                        $statusLabel = 'COMPLETED';
                        if ($session->status === \App\Enums\SessionStatus::EXPIRED) {
                            $statusLabel = 'EXPIRED';
                        } elseif ($session->status === \App\Enums\SessionStatus::DEAL_FAILED) {
                            $statusLabel = 'FAILED';
                        }
                        
                        return [
                            'id' => $session->id,
                            'inquiry_id' => $inquiry->id,
                            'title' => $inquiry->title,
                            'status' => $session->status->value,
                            'status_label' => $statusLabel,
                            'partners_matched' => $selectedPartnersCount,
                            'quantity' => $inquiry->items->sum('quantity'),
                            'quantity_unit' => $inquiry->items->first()?->quantity_unit ?? 'units',
                            'created_at' => $inquiry->created_at,
                            'can_republish' => $session->canRepublish()->where('id', $session->id)->exists(),
                        ];
                    })->values(),
                ];
            })->values();
            
            return Response::success('Session history retrieved successfully', [
                'sessions' => $transformedSessions,
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'total' => $sessions->total(),
                    'per_page' => $sessions->perPage(),
                    'last_page' => $sessions->lastPage(),
                ],
            ]);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lock session and select dealers
     */
    public function lock(\App\Http\Requests\LockSessionRequest $request, \App\Models\MatchingSession $session)
    {
        try {
            \Illuminate\Support\Facades\Gate::authorize('lock', $session);
            
            $user = $request->user();
            $selectedDealerIds = $request->validated()['selected_dealer_ids'];
            
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            // Update session status
            $session->update([
                'status' => \App\Enums\SessionStatus::LOCKED,
                'locked_at' => now(),
                'chat_enabled' => true,
                'full_specs_visible' => true,
                'brand_identity_visible' => true,
            ]);
            
            // Update inquiry status
            $inquiry = $session->inquiry;
            $inquiry->update([
                'status' => \App\Enums\InquiryStatus::LOCKED,
                'locked_at' => now(),
                'selected_dealers_count' => count($selectedDealerIds),
            ]);
            
            // Hide from non-selected dealers
            app(\App\Services\MatchmakingService::class)->hideFromNonSelectedDealers($inquiry, $selectedDealerIds);
            
            // Create session participants for selected dealers
            foreach ($selectedDealerIds as $dealerId) {
                \App\Models\SessionParticipant::create([
                    'session_id' => $session->id,
                    'participant_type' => 'dealer',
                    'participant_id' => $dealerId,
                    'role' => 'responder',
                    'can_see_full_specs' => true,
                    'can_see_exact_location' => true,
                    'can_see_brand_identity' => true,
                    'can_chat' => true,
                    'status' => 'active',
                    'is_selected' => true,
                    'selected_at' => now(),
                    'joined_at' => now(),
                ]);
            }
            
            // Create participant for poster
            $poster = $inquiry->poster;
            \App\Models\SessionParticipant::create([
                'session_id' => $session->id,
                'participant_type' => $inquiry->poster_type,
                'participant_id' => $inquiry->poster_id,
                'role' => 'poster',
                'can_see_full_specs' => true,
                'can_see_exact_location' => true,
                'can_see_brand_identity' => true,
                'can_chat' => true,
                'status' => 'active',
                'joined_at' => now(),
            ]);
            
            // Create chat thread
            \App\Models\ChatThread::create([
                'session_id' => $session->id,
                'thread_type' => count($selectedDealerIds) > 1 ? 'one_to_few' : 'one_to_one',
                'is_active' => true,
            ]);
            
            \Illuminate\Support\Facades\DB::commit();
            
            $session->load(['inquiry', 'participants', 'chatThread']);
            
            return Response::success('Session locked successfully', $session);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get active sessions (Sourcing Hub)
     * Screen: Active Sessions (Sourcing Hub) Screen
     */
    public function getActive()
    {
        try {
            $user = request()->user();
            $filter = request()->input('filter', 'all'); // all, finding_matches, active, locked
            
            $query = \App\Models\MatchingSession::query()
                ->when($user->brand || $user->converter, function ($q) use ($user) {
                    $q->visibleToBrand($user->brand?->id ?? $user->converter?->id);
                })
                ->when($user->dealer, function ($q) use ($user) {
                    $q->visibleToDealer($user->dealer->id);
                })
                ->with(['inquiry.items', 'participants']);
            
            // Apply filters
            if ($filter === 'finding_matches') {
                $query->where('status', \App\Enums\SessionStatus::MATCHING);
            } elseif ($filter === 'active') {
                $query->whereIn('status', [
                    \App\Enums\SessionStatus::RESPONSES_RECEIVED,
                    \App\Enums\SessionStatus::LOCKED,
                    \App\Enums\SessionStatus::CHAT_ACTIVE,
                ]);
            } elseif ($filter === 'locked') {
                $query->where('status', \App\Enums\SessionStatus::LOCKED);
            } else {
                // All active sessions
                $query->whereIn('status', [
                    \App\Enums\SessionStatus::MATCHING,
                    \App\Enums\SessionStatus::RESPONSES_RECEIVED,
                    \App\Enums\SessionStatus::LOCKED,
                    \App\Enums\SessionStatus::CHAT_ACTIVE,
                ]);
            }
            
            $sessions = $query->orderBy('created_at', 'desc')
                ->paginate(request()->per_page ?? 15);
            
            // Transform sessions for frontend
            $sessions->getCollection()->transform(function ($session) {
                $inquiry = $session->inquiry;
                
                // Calculate countdown
                $countdown = null;
                if ($session->expires_at) {
                    $secondsLeft = max(0, now()->diffInSeconds($session->expires_at, false));
                    if ($secondsLeft > 0) {
                        $days = floor($secondsLeft / 86400);
                        $hours = floor(($secondsLeft % 86400) / 3600);
                        $minutes = floor(($secondsLeft % 3600) / 60);
                        $secs = $secondsLeft % 60;
                        
                        $countdown = [
                            'days' => $days,
                            'hours' => $hours,
                            'minutes' => $minutes,
                            'seconds' => $secs,
                            'formatted' => sprintf('%02d DAYS %02d HOURS %02d MINS %02d SECS', $days, $hours, $minutes, $secs),
                        ];
                    }
                }
                
                // Get response count
                $responsesCount = $inquiry->responses_count ?? $inquiry->responses()->count();
                $matchedDealersCount = $inquiry->matched_dealers_count ?? 0;
                
                // Determine status label
                $statusLabel = 'ACTIVE';
                if ($session->status === \App\Enums\SessionStatus::MATCHING) {
                    $statusLabel = 'FINDING';
                } elseif ($session->status === \App\Enums\SessionStatus::LOCKED) {
                    $statusLabel = 'LOCKED';
                } elseif ($session->status === \App\Enums\SessionStatus::RESPONSES_RECEIVED) {
                    $statusLabel = 'ACTIVE';
                }
                
                return [
                    'id' => $session->id,
                    'inquiry_id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'status' => $session->status->value,
                    'status_label' => $statusLabel,
                    'urgency' => $inquiry->urgency,
                    'created_at' => $inquiry->created_at,
                    'items' => $inquiry->items->map(function ($item) {
                        return [
                            'material_category' => $item->material_category,
                            'quantity' => $item->quantity,
                            'quantity_unit' => $item->quantity_unit,
                        ];
                    }),
                    'countdown' => $countdown,
                    'responses_received' => $responsesCount,
                    'matched_dealers_count' => $matchedDealersCount,
                    'matching_progress' => $session->status === \App\Enums\SessionStatus::MATCHING ? [
                        'matched' => $matchedDealersCount,
                        'total' => 15, // Target dealers to match
                        'status' => 'Scanning suppliers...',
                    ] : null,
                ];
            });
            
            return Response::success('Active sessions retrieved successfully', $sessions);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Republish session
     */
    public function republish(\App\Models\MatchingSession $session)
    {
        try {
            \Illuminate\Support\Facades\Gate::authorize('republish', $session);
            
            // Republish the inquiry
            $inquiryController = new \App\Http\Controllers\api\InquiryController(
                app(\App\Services\MatchmakingService::class)
            );
            
            return $inquiryController->republish(request(), $session->inquiry);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mark deal as failed
     */
    public function markDealFailed(\App\Models\MatchingSession $session)
    {
        try {
            \Illuminate\Support\Facades\Gate::authorize('markDealFailed', $session);
            
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            $session->update([
                'status' => \App\Enums\SessionStatus::DEAL_FAILED,
                'cooldown_until' => now()->addDays(7),
            ]);
            
            $session->inquiry->update([
                'status' => \App\Enums\InquiryStatus::DEAL_FAILED,
                'cooldown_until' => now()->addDays(7),
            ]);
            
            // Make chat read-only
            if ($session->chatThread) {
                $session->chatThread->update([
                    'is_read_only' => true,
                    'archived_at' => now(),
                ]);
            }
            
            \Illuminate\Support\Facades\DB::commit();
            
            return Response::success('Deal marked as failed', $session);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}




