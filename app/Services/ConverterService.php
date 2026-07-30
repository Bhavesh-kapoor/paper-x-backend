<?php

namespace App\Services;

use App\Enums\ConverterStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\ResponseStatus;
use App\Enums\RTDOrderStatus;
use App\Enums\SessionStatus;
use App\Models\Converter;
use App\Models\Inquiry;
use App\Models\RtdOrder;
use App\Models\RtdProduct;
use App\Models\InquiryItem;
use App\Models\Machine;
use App\Models\MatchingSession;
use App\Models\Response;
use App\Models\User;
use App\Domain\MatchEngine\MatchEngineOrchestrator;
use App\Jobs\EnsureUserMatchesJob;
use App\Services\MatchmakingService;
use App\Services\Concerns\ChargesPostingFee;
use Illuminate\Support\Facades\DB;

class ConverterService
{
    use ChargesPostingFee;

    public function __construct(
        protected MatchEngineOrchestrator $matchEngineOrchestrator,
        protected MatchmakingService $matchmakingService
    ) {
    }
    public function completeProfile(array $data, int $userId): Converter
    {
        $converter = DB::transaction(function () use ($data, $userId) {
            $converter = Converter::firstOrCreate(
                ['user_id' => $userId],
                ['status' => ConverterStatus::PENDING]
            );

            $converter->update([
                'converter_type_custom' => $data['converter_type_custom'] ?? null,
                'capacity_daily' => $data['capacity_daily'] ?? null,
                'capacity_monthly' => $data['capacity_monthly'] ?? null,
                'capacity_unit' => $data['capacity_unit'] ?? null,
                'factory_address' => $data['factory_address'] ?? null,
                'factory_city' => $data['factory_city'] ?? null,
                'factory_state' => $data['factory_state'] ?? null,
                'factory_latitude' => $data['factory_latitude'] ?? null,
                'factory_longitude' => $data['factory_longitude'] ?? null,
                'profile_complete' => true,
                'status' => ConverterStatus::ACTIVE,
            ]);

            // Sync relationships
            if (isset($data['converter_type_ids'])) {
                $converter->converterTypes()->sync($data['converter_type_ids']);
            }

            if (isset($data['finished_product_ids'])) {
                $converter->finishedProducts()->sync($data['finished_product_ids']);
            }

            if (isset($data['machine_ids'])) {
                $converter->machines()->sync($data['machine_ids']);
            }

            if (isset($data['scrap_type_ids'])) {
                $converter->scrapTypes()->sync($data['scrap_type_ids']);
            }

            if (isset($data['raw_material_ids'])) {
                $converter->rawMaterials()->sync($data['raw_material_ids']);
            }

            return $converter->load(['converterTypes', 'finishedProducts', 'machines', 'scrapTypes', 'rawMaterials']);
        });

        // Retroactive matching runs off the request path (after commit + after the
        // HTTP response is flushed) so registration returns immediately.
        EnsureUserMatchesJob::dispatchAfterResponse($userId);

        return $converter;
    }

    public function getDashboard(int $userId): array
    {
        $converter = Converter::where('user_id', $userId)->first();
        
        if (!$converter) {
            return [
                'profile_completion_percentage' => 0,
                'active_sessions_count' => 0,
                'my_inquiries_count' => 0,
                'responses_received_count' => 0,
                'unread_notifications_count' => 0,
                'active_sessions' => [],
                'rtd_snapshot' => [
                    'active_listings' => 0,
                    'active_listings_change' => '',
                    'pending_orders' => 0,
                    'pending_orders_label' => 'Action needed',
                    'total_orders' => 0,
                    'orders_label' => 'All time',
                    'revenue' => '₹0',
                    'revenue_change' => '',
                ],
            ];
        }

        $converterId = $converter->id;
        
        // Active sessions = own posted + matched opportunities from other users
        // (mirrors SessionController@getActive / the full Sessions dashboard).
        $activeSessionsCount = MatchingSession::visibleToConverter($converterId)
            ->where('status', SessionStatus::ACTIVE)
            ->where('expires_at', '>', now())
            ->count();

        $myInquiries = Inquiry::where('poster_id', $converterId)
            ->where('poster_type', 'converter')
            ->count();

        $responsesReceived = \App\Models\MatchmakingLog::whereHas('inquiry', function ($query) use ($converterId) {
            $query->where('poster_id', $converterId)
                ->where('poster_type', 'converter');
        })->whereNotNull('responded_at')->count();

        $unreadNotifications = \App\Models\Notification::where('user_id', $userId)
            ->where('read_at', null)
            ->count();

        // Top 3 active sessions for dashboard: own posted + matched opportunities
        $activeSessionModels = MatchingSession::visibleToConverter($converterId)
            ->where('status', SessionStatus::ACTIVE)
            ->where('expires_at', '>', now())
            ->with(['inquiry.items'])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        // Batch matchmaking-log counts for all sessions' inquiries in ONE query
        // (avoids 2 queries per session N+1).
        $inquiryIds = $activeSessionModels->pluck('inquiry.id')->filter()->values();
        $logCounts = $inquiryIds->isEmpty()
            ? collect()
            : \App\Models\MatchmakingLog::whereIn('inquiry_id', $inquiryIds)
                ->selectRaw('inquiry_id,
                    SUM(CASE WHEN is_visible = 1 THEN 1 ELSE 0 END) as matched_count,
                    SUM(CASE WHEN responded_at IS NOT NULL THEN 1 ELSE 0 END) as responded_count')
                ->groupBy('inquiry_id')
                ->get()
                ->keyBy('inquiry_id');

        $activeSessions = $activeSessionModels
            ->map(function ($session) use ($logCounts) {
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
                
                // Keep dashboard session counts aligned with poster-detail/session active logic.
                // Counts come from the single batched aggregate above (no per-session query).
                $counts = $logCounts->get($inquiry->id);
                $matchedDealersCount = (int) ($counts->matched_count ?? 0);
                $responsesCount = (int) ($counts->responded_count ?? 0);
                
                // Determine status label based on session and inquiry status
                $statusLabel = 'ACTIVE';
                $inquiryStatus = $inquiry->status;
                
                if ($inquiryStatus === InquiryStatus::MATCHING) {
                    $statusLabel = 'FINDING';
                } elseif ($session->locked_at && $session->locked_at <= now()) {
                    $statusLabel = 'LOCKED';
                } elseif ($inquiryStatus === InquiryStatus::RESPONSES_RECEIVED) {
                    $statusLabel = 'ACTIVE';
                }
                
                return [
                    'id' => $session->id,
                    'inquiry_id' => $inquiry->id,
                    'title' => $inquiry->title ?? 'Untitled Inquiry',
                    'status' => $session->status->value,
                    'status_label' => $statusLabel,
                    'urgency' => $inquiry->urgency ?? 'normal',
                    'created_at' => $inquiry->created_at->toIso8601String(),
                    'items' => $inquiry->items->map(function ($item) {
                        return [
                            'material_category' => $item->material_category ?? '',
                            'quantity' => $item->quantity ?? 0,
                            'quantity_unit' => $item->quantity_unit ?? '',
                        ];
                    })->toArray(),
                    'countdown' => $countdown,
                    'responses_received' => $responsesCount,
                    'matched_dealers_count' => $matchedDealersCount,
                    'matching_progress' => $session->status === SessionStatus::MATCHING ? [
                        'matched' => $matchedDealersCount,
                        'total' => 15, // Target dealers to match
                        'status' => 'Scanning suppliers...',
                    ] : null,
                ];
            })
            ->toArray();

        // RTD (Ready-to-Dispatch) snapshot for dashboard
        $activeListings = RtdProduct::where('converter_id', $userId)->where('status', 'active')->count();
        $pendingOrders = RtdOrder::where('converter_id', $userId)->where('status', RTDOrderStatus::REQUESTED)->count();
        $totalOrders = RtdOrder::where('converter_id', $userId)->count();
        $revenueSum = (float) RtdOrder::where('converter_id', $userId)->where('status', RTDOrderStatus::COMPLETED)->sum('total_amount');
        $revenueFormatted = '₹' . number_format($revenueSum, 0);

        return [
            'profile_completion_percentage' => $converter->profile_complete ? 100 : 0,
            'active_sessions_count' => $activeSessionsCount,
            'my_inquiries_count' => $myInquiries,
            'responses_received_count' => $responsesReceived,
            'unread_notifications_count' => $unreadNotifications,
            'active_sessions' => $activeSessions, // Top 3 active sessions
            'rtd_snapshot' => [
                'active_listings' => $activeListings,
                'active_listings_change' => $activeListings > 0 ? 'Live' : 'No listings',
                'pending_orders' => $pendingOrders,
                'pending_orders_label' => $pendingOrders > 0 ? 'Action needed' : 'None',
                'total_orders' => $totalOrders,
                'orders_label' => 'All time',
                'revenue' => $revenueFormatted,
                'revenue_change' => $revenueSum > 0 ? 'From RTD' : '',
            ],
        ];
    }

    /**
     * Post a machine requirement (buy/sell) as converter.
     * Creates an Inquiry only (no MachineListing); poster_type = converter.
     */
    public function postMachine(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $converter = Converter::where('user_id', $userId)->firstOrFail();
            $machine = Machine::findOrFail($data['machine_id']);
            $title = $data['title'] ?? 'Machine: ' . $machine->name;
            $visibility = $data['visibility'] ?? 'machine_dealers';

            $inquiry = Inquiry::create([
                'poster_id' => $converter->id,
                'poster_type' => 'converter',
                'inquiry_type' => InquiryType::MACHINE,
                'intent' => $data['intent'],
                'title' => $title,
                'description' => $data['description'] ?? null,
                'urgency' => $data['urgency'],
                'quantity' => 1,
                'quantity_unit' => 'unit',
                'machine_listing_id' => null,
                'machine_condition' => $data['condition'] ?? null,
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status' => InquiryStatus::MATCHING,
                'posting_fee_paid' => false,
                'posting_fee_amount' => null,
                'visibility' => $visibility,
            ]);

            $inquiry->machines()->attach($data['machine_id']);

            // Server-authoritative machine posting fee (price-range bracket).
            $quote = $this->chargePostingFee($userId, [
                'role' => 'converter',
                'inquiry_type' => 'machine',
                'machine_price_range' => $data['machine_price_range'] ?? null,
                'urgency' => $data['urgency'] ?? 'normal',
            ], $inquiry->id, ['source' => 'converter_machine_post']);
            $inquiry->update([
                'posting_fee_paid' => true,
                'posting_fee_amount' => $quote['total'],
            ]);

            $session = MatchingSession::create([
                'inquiry_id' => $inquiry->id,
                'status' => SessionStatus::ACTIVE,
                'locked_at' => null,
                'expires_at' => now()->addHours(24),
                'discovery_start' => now(),
                'active_session_start' => now(),
                'is_visible_to_dealers' => true,
                'is_visible_to_brand' => false,
            ]);

            $inquiry->setRelation('session', $session);

            $result = $this->matchEngineOrchestrator->runMatchmaking($inquiry);
            $this->matchmakingService->notifyMatchedRecipients(
                $inquiry,
                $result['dealer_ids'] ?? [],
                $result['converter_ids'] ?? [],
                $result['machine_dealer_ids'] ?? []
            );

            return [
                'inquiry_id' => $inquiry->id,
                'status' => $inquiry->status->value,
            ];
        });
    }

    public function getRequirements(int $userId, array $filters = []): array
    {
        $converter = Converter::where('user_id', $userId)->firstOrFail();

        if (!$converter->profile_complete || $converter->status !== ConverterStatus::ACTIVE) {
            return [
                'requirements' => [],
                'pagination' => [
                    'current_page' => 1,
                    'total' => 0,
                    'per_page' => $filters['per_page'] ?? 15,
                    'last_page' => 1,
                ],
            ];
        }

        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        // Get brand inquiries (requirements)
        $query = Inquiry::where('poster_type', 'brand')
            ->where('status', InquiryStatus::MATCHING)
            ->with(['brand', 'session'])
            ->orderBy('created_at', 'desc');

        // Filter by city (match converter's city)
        if ($converter->factory_city) {
            $query->whereHas('brand', function ($q) use ($converter) {
                $q->where('city', $converter->factory_city);
            });
        }

        // Filter by requirement type
        if (isset($filters['requirement_type'])) {
            $query->where('requirement_type', $filters['requirement_type']);
        }

        // Filter by urgency
        if (isset($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
        }

        $inquiries = $query->paginate($perPage, ['*'], 'page', $page);

        $requirements = $inquiries->map(function ($inquiry) {
            return [
                'id' => $inquiry->id,
                'title' => $inquiry->title,
                'description' => $inquiry->description,
                'requirement_type' => $inquiry->requirement_type,
                'packaging_type' => $inquiry->packaging_type,
                'quantity_range' => $inquiry->quantity_range,
                'timeline' => $inquiry->timeline,
                'special_needs' => $inquiry->special_needs,
                'urgency' => $inquiry->urgency,
                'location' => $inquiry->location,
                'design_attachments' => $inquiry->design_attachments,
                'has_active_session' => $inquiry->session !== null,
                'session_id' => $inquiry->session?->id,
                'created_at' => $inquiry->created_at->toIso8601String(),
                // Brand details hidden until session lock
                'brand_name' => null,
                'brand_company_name' => null,
            ];
        });

        return [
            'requirements' => $requirements->toArray(),
            'pagination' => [
                'current_page' => $inquiries->currentPage(),
                'total' => $inquiries->total(),
                'per_page' => $inquiries->perPage(),
                'last_page' => $inquiries->lastPage(),
            ],
        ];
    }

    public function respondToRequirement(int $inquiryId, int $userId, array $data): array
    {
        return DB::transaction(function () use ($inquiryId, $userId, $data) {
            $converter = Converter::where('user_id', $userId)->firstOrFail();
            $inquiry = Inquiry::findOrFail($inquiryId);

            if ($inquiry->poster_type !== 'brand') {
                throw new \Exception('This inquiry is not a brand requirement', 400);
            }

            if ($inquiry->status !== InquiryStatus::MATCHING) {
                throw new \Exception('Inquiry is not available for response', 400);
            }

            // Check if converter already responded
            $existingResponse = Response::where('inquiry_id', $inquiryId)
                ->where('responder_id', $userId)
                ->where('responder_type', 'converter')
                ->first();

            if ($existingResponse) {
                throw new \Exception('You have already responded to this requirement', 400);
            }

            // Get session if exists
            $session = MatchingSession::where('inquiry_id', $inquiryId)->first();

            // Create response
            $response = Response::create([
                'inquiry_id' => $inquiryId,
                'responder_id' => $userId,
                'responder_type' => 'converter',
                'quantity_offered' => $data['quantity_offered'] ?? null,
                'quantity_unit' => $data['quantity_unit'] ?? null,
                'quoted_price' => $data['quoted_price'] ?? null,
                'price_unit' => $data['price_unit'] ?? null,
                'price_status' => $data['price_status'] ?? null,
                'additional_details' => $data['additional_details'] ?? null,
                'status' => ResponseStatus::PENDING,
                'session_id' => $session?->id,
            ]);

            return [
                'response_id' => $response->id,
                'message' => 'Response submitted successfully',
            ];
        });
    }

    public function postRequirement(array $data, int $userId): Inquiry
    {
        return DB::transaction(function () use ($data, $userId) {
            $converter = Converter::where('user_id', $userId)->firstOrFail();

            $visibility = $data['visibility'] ?? 'dealers';

            // Generate title from material and quantity if not provided
            $title = $data['title'] ?? null;
            if (!$title && isset($data['material_id'])) {
                $material = \App\Models\Material::find($data['material_id']);
                $title = ($material ? $material->name : 'Material') . ' - ' . $data['quantity'] . ' ' . ($data['quantity_unit'] ?? '');
            }

            // Create inquiry
            $inquiry = Inquiry::create([
                'poster_id' => $converter->id, // Store converter ID, not user ID
                'poster_type' => 'converter',
                'inquiry_type' => $data['inquiry_type'], // Always 'material'
                'intent' => $data['intent'], // 'buy' or 'sell'
                'title' => $title,
                'description' => null, // Not in new requirements
                'urgency' => $data['urgency'],
                'quantity' => $data['quantity'],
                'quantity_unit' => $data['quantity_unit'],
                'size' => $data['size'],
                'size_unit' => $data['size_unit'],
                'thickness' => $data['thickness'],
                'thickness_unit' => $data['thickness_unit'],
                'visibility' => $visibility,
                'location' => $data['location'],
                'location_source' => $data['location_source'],
                'location_id' => null, // Converters don't have saved locations
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'status' => InquiryStatus::MATCHING,
                'posted_at' => now(),
                'matching_started_at' => now(),
                'is_visible_to_dealers' => in_array($visibility, ['dealers', 'all'], true),
                'is_visible_to_brand' => false, // NEVER visible to brands (converter-posted)
            ]);

            // Attach single material
            if (isset($data['material_id'])) {
                $inquiry->materials()->sync([$data['material_id']]);
            }

            // Attach finishes if provided
            if (isset($data['finish_ids']) && is_array($data['finish_ids']) && !empty($data['finish_ids'])) {
                $inquiry->finishes()->sync($data['finish_ids']);
            }

            // Create inquiry item for matchmaking (required for new system)
            $material = \App\Models\Material::find($data['material_id'] ?? null);
            $materialCategory = $material ? $material->name : null;
            
            // Convert thickness to GSM/MM based on unit
            $thicknessUnit = strtolower($data['thickness_unit'] ?? 'gsm');
            $thicknessGsm = null;
            $thicknessMm = null;
            if (isset($data['thickness']) && isset($data['thickness_unit'])) {
                if (strtoupper($data['thickness_unit']) === 'GSM') {
                    $thicknessGsm = $data['thickness'];
                } elseif (strtoupper($data['thickness_unit']) === 'MM') {
                    $thicknessMm = $data['thickness'];
                }
            }

            InquiryItem::create([
                'inquiry_id' => $inquiry->id,
                'material_id' => $data['material_id'] ?? null,
                'material_category' => $materialCategory,
                'finish_coating' => null, // Can be enhanced later
                'thickness_gsm' => $thicknessGsm,
                'thickness_mm' => $thicknessMm,
                'thickness_unit' => $thicknessUnit,
                'thickness_tolerance_percent' => $data['urgency'] === 'urgent' ? 10.0 : 5.0,
                'thickness_tolerance_absolute' => $data['urgency'] === 'urgent' ? 0.3 : 0.2,
                'quantity' => $data['quantity'],
                'quantity_unit' => $data['quantity_unit'],
                'additional_specs' => null,
            ]);

            // Server-authoritative raw-material posting fee (value band × quantity bucket).
            $quote = $this->chargePostingFee($userId, [
                'role' => 'converter',
                'inquiry_type' => $data['inquiry_type'] ?? 'material',
                'material_id' => $data['material_id'] ?? null,
                'thickness' => $data['thickness'] ?? null,
                'thickness_unit' => $data['thickness_unit'] ?? null,
                'size' => $data['size'] ?? null,
                'size_unit' => $data['size_unit'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'quantity_unit' => $data['quantity_unit'] ?? null,
                'urgency' => $data['urgency'] ?? 'normal',
            ], $inquiry->id, ['source' => 'converter_requirement_post']);
            $inquiry->update([
                'posting_fee_paid' => true,
                'posting_fee_amount' => $quote['total'],
            ]);

            // Create matching session (required for sessions to appear)
            $session = MatchingSession::create([
                'inquiry_id' => $inquiry->id,
                'status' => SessionStatus::ACTIVE, // Using ACTIVE (legacy) until enum is updated
                'locked_at' => now(), // Required field, set to now
                'expires_at' => now()->addHours(24), // 24 hours expiry
                'discovery_start' => now(),
                'active_session_start' => now(),
                'is_visible_to_dealers' => in_array($visibility, ['dealers', 'all'], true),
                'is_visible_to_brand' => false, // Never visible to brands (converter-posted)
            ]);

            // Trigger matchmaking via orchestrator (V1 or V2 based on config)
            $result = $this->matchEngineOrchestrator->runMatchmaking($inquiry);
            $this->matchmakingService->notifyMatchedRecipients(
                $inquiry,
                $result['dealer_ids'] ?? [],
                $result['converter_ids'] ?? [],
                $result['machine_dealer_ids'] ?? []
            );

            return $inquiry->load(['materials', 'finishes', 'items', 'session']);
        });
    }
}





