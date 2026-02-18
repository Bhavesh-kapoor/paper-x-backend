<?php

namespace App\Services;

use App\Enums\ConverterStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\ResponseStatus;
use App\Enums\SessionStatus;
use App\Models\Converter;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\Machine;
use App\Models\MatchingSession;
use App\Models\Response;
use App\Services\MatchmakingService;
use Illuminate\Support\Facades\DB;

class ConverterService
{
    public function __construct(
        protected MatchmakingService $matchmakingService
    ) {
    }
    public function completeProfile(array $data, int $userId): Converter
    {
        return DB::transaction(function () use ($data, $userId) {
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
            ];
        }

        $converterId = $converter->id;
        
        // Get active sessions count (own posted only – sessions are private per user)
        $activeSessionsCount = MatchingSession::ownSessionsByConverter($converterId)
            ->where('status', SessionStatus::ACTIVE)
            ->where('expires_at', '>', now())
            ->count();

        $myInquiries = Inquiry::where('poster_id', $converterId)
            ->where('poster_type', 'converter')
            ->count();

        $responsesReceived = \App\Models\Response::whereHas('inquiry', function ($query) use ($converterId) {
            $query->where('poster_id', $converterId)
                ->where('poster_type', 'converter');
        })->count();

        $unreadNotifications = \App\Models\Notification::where('user_id', $userId)
            ->where('read_at', null)
            ->count();

        // Get top 5 active sessions for dashboard (own posted only)
        $activeSessions = MatchingSession::ownSessionsByConverter($converterId)
            ->where('status', SessionStatus::ACTIVE)
            ->where('expires_at', '>', now())
            ->with(['inquiry.items', 'inquiry.responses'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($session) {
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
                $responsesCount = $inquiry->responses_count ?? $inquiry->responses->count() ?? 0;
                $matchedDealersCount = $inquiry->matched_dealers_count ?? 0;
                
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

        return [
            'profile_completion_percentage' => $converter->profile_complete ? 100 : 0,
            'active_sessions_count' => $activeSessionsCount,
            'my_inquiries_count' => $myInquiries,
            'responses_received_count' => $responsesReceived,
            'unread_notifications_count' => $unreadNotifications,
            'active_sessions' => $activeSessions, // Top 5 active sessions
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
                'posting_fee_paid' => $data['posting_fee_paid'] ?? false,
                'posting_fee_amount' => $data['posting_fee_amount'] ?? null,
            ]);

            $inquiry->machines()->attach($data['machine_id']);

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

            // Trigger matchmaking based on visibility
            if (in_array($visibility, ['dealers', 'all'], true)) {
                $matchedDealers = $this->matchmakingService->findMatchingDealers($inquiry);
                $this->matchmakingService->notifyMatchedDealers($inquiry, $matchedDealers);
            }

            if (in_array($visibility, ['converters', 'all'], true)) {
                $this->matchmakingService->findMatchingConverters($inquiry);
                // Notifications for converters can be added similarly to dealers
            }

            return $inquiry->load(['materials', 'finishes', 'items', 'session']);
        });
    }
}





