<?php

namespace App\Services;

use App\Enums\DealerStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\InquiryIntent;
use App\Enums\SessionStatus;
use App\Models\Dealer;
use App\Models\DealerLocation;
use App\Models\DealerMaterialDetail;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\MatchingSession;
use App\Models\User;
use App\Domain\MatchEngine\MatchEngineOrchestrator;
use App\Jobs\EnsureUserMatchesJob;
use App\Services\MatchmakingService;
use App\Services\Concerns\ChargesPostingFee;
use Illuminate\Support\Facades\DB;

class DealerService
{
    use ChargesPostingFee;

    public function __construct(
        protected MatchEngineOrchestrator $matchEngineOrchestrator,
        protected MatchmakingService $matchmakingService
    ) {
    }

    public function completeProfile(array $data, int $userId): Dealer
    {
        $dealer = DB::transaction(function () use ($data, $userId) {
            $dealer = Dealer::firstOrCreate(
                ['user_id' => $userId],
                ['status' => DealerStatus::PENDING]
            );

            $dealer->update([
                'profile_complete' => true,
                'status' => DealerStatus::ACTIVE,
                'grades' => $data['grades'] ?? null,
                'capacity_daily' => $data['capacity_daily'],
                'capacity_monthly' => $data['capacity_monthly'],
                'capacity_unit' => $data['capacity_unit'],
            ]);

            // Sync materials - extract material_ids from the materials array
            $materialIds = [];
            if (isset($data['materials']) && is_array($data['materials'])) {
                $materialIds = array_column($data['materials'], 'material_id');
            } elseif (isset($data['materials_dealt_in']) && is_array($data['materials_dealt_in'])) {
                // Fallback for old format
                $materialIds = $data['materials_dealt_in'];
            }
            
            if (!empty($materialIds)) {
                $dealer->materials()->sync($materialIds);
            }

            // Handle dealer material details (mill/brand relationships, finishes, thickness ranges)
            if (isset($data['materials']) && is_array($data['materials'])) {
                // Delete old material details
                $dealer->materialDetails()->delete();
                
                // Create new material details
                foreach ($data['materials'] as $materialData) {
                    // Use mill_brand_id if provided, otherwise use brand_id
                    $brandId = $materialData['mill_brand_id'] ?? $materialData['brand_id'] ?? null;
                    
                    // Map relationship to agent_type if provided
                    $agentType = $materialData['agent_type'] ?? null;
                    if (!$agentType && isset($materialData['relationship'])) {
                        if ($materialData['relationship'] === 'authorized-agent') {
                            $agentType = 'AUTHORIZED_AGENT';
                        } elseif ($materialData['relationship'] === 'independent-dealer') {
                            $agentType = 'DEALER';
                        }
                    }
                    
                    DealerMaterialDetail::create([
                        'dealer_id' => $dealer->id,
                        'material_id' => $materialData['material_id'],
                        'brand_id' => $brandId,
                        'agent_type' => $agentType,
                        'finish_ids' => $materialData['finish_ids'] ?? null,
                        'thickness_ranges' => $materialData['thickness_ranges'] ?? [],
                    ]);
                }
                // Ensure dealer_materials pivot stays in sync with material details (used by matchmaking)
                $dealer->materials()->sync(array_column($data['materials'], 'material_id'));
            }

            // Sync machines (optional)
            if (isset($data['machines_available']) && !empty($data['machines_available'])) {
                $dealer->machines()->sync($data['machines_available']);
            }

            // Delete old locations and create new ones
            $dealer->locations()->delete();
            if (isset($data['locations']) && is_array($data['locations'])) {
                foreach ($data['locations'] as $locationData) {
                    DealerLocation::create([
                        'dealer_id' => $dealer->id,
                        'type' => $locationData['type'],
                        'address' => $locationData['address'] ?? null,
                        'latitude' => $locationData['latitude'],
                        'longitude' => $locationData['longitude'],
                        'city' => $locationData['city'] ?? null,
                        'state' => $locationData['state'] ?? null,
                        'pincode' => $locationData['pincode'] ?? null,
                    ]);
                }
            }

            return $dealer->load(['materials', 'machines', 'locations']);
        });

        // Retroactive matching runs off the request path (after commit + after the
        // HTTP response is flushed) so registration returns immediately.
        EnsureUserMatchesJob::dispatchAfterResponse($userId);

        return $dealer;
    }

    /**
     * Partial per-section update from the Registration Details editor.
     * Phase 1 handles company (common), capacity, and machines. Materials &
     * locations remain full-profile only. Never flips profile_complete.
     */
    public function updateSection(array $data, int $userId): Dealer
    {
        $dealer = DB::transaction(function () use ($data, $userId) {
            $dealer = Dealer::firstOrCreate(
                ['user_id' => $userId],
                ['status' => DealerStatus::PENDING]
            );

            $userFields = array_intersect_key(
                $data,
                array_flip(['company_name', 'gst_in', 'city', 'state', 'operation_area'])
            );
            if (!empty($userFields)) {
                User::where('id', $userId)->update($userFields);
            }

            $scalars = array_intersect_key($data, array_flip([
                'capacity_daily', 'capacity_monthly', 'capacity_unit',
            ]));
            if (!empty($scalars)) {
                $dealer->update($scalars);
            }

            if (array_key_exists('machine_ids', $data)) {
                $dealer->machines()->sync($data['machine_ids'] ?? []);
            }

            return $dealer->load(['materials', 'machines', 'locations']);
        });

        EnsureUserMatchesJob::dispatchAfterResponse($userId);

        return $dealer;
    }

    public function getDashboard(int $userId): array
    {
        $dealer = Dealer::where('user_id', $userId)
            ->withCount(['materials', 'locations'])
            ->first();

        // If dealer doesn't exist, return empty dashboard
        if (!$dealer) {
            return [
                'profile_completion_percentage' => 0,
                'active_opportunities_count' => 0,
                'active_inquiries_count' => 0,
                'locked_sessions_count' => 0,
                'expired_sessions_count' => 0,
                'unread_notifications_count' => 0,
                'posted_requirements_count' => 0,
            ];
        }

        $profileCompletion = $this->calculateProfileCompletion($dealer);

        // Own sessions only – sessions are private per user (what dealer posted)
        $activeOpportunities = MatchingSession::ownSessionsByDealer($dealer->id)
            ->where('status', SessionStatus::ACTIVE)
            ->where('expires_at', '>', now())
            ->count();

        // Inquiries: posts with < 10 people who responded (expressed interest) – open until 10 respond
        $activeInquiriesCount = MatchingSession::ownSessionsByDealer($dealer->id)
            ->whereIn('status', [SessionStatus::ACTIVE, SessionStatus::LOCKED])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereHas('inquiry', function ($q) {
                $q->whereRaw('(SELECT COUNT(*) FROM matchmaking_logs WHERE matchmaking_logs.inquiry_id = inquiries.id AND matchmaking_logs.responded_at IS NOT NULL) < 10');
            })
            ->count();

        // Locked sessions: posts with >= 10 people who responded – removed from active inquiries
        $lockedSessions = MatchingSession::ownSessionsByDealer($dealer->id)
            ->whereIn('status', [SessionStatus::ACTIVE, SessionStatus::LOCKED])
            ->whereHas('inquiry', function ($iq) {
                $iq->whereRaw('(SELECT COUNT(*) FROM matchmaking_logs WHERE matchmaking_logs.inquiry_id = inquiries.id AND matchmaking_logs.responded_at IS NOT NULL) >= 10');
            })
            ->count();

        $expiredSessions = MatchingSession::ownSessionsByDealer($dealer->id)
            ->where('status', SessionStatus::EXPIRED)
            ->count();

        $unreadNotifications = \App\Models\Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        // Count dealer's posted requirements
        $dealerId = $dealer ? $dealer->id : null;
        $postedRequirementsCount = $dealerId ? Inquiry::where('poster_id', $dealerId)
            ->where('poster_type', 'dealer')
            ->count() : 0;

        return [
            'profile_completion_percentage' => $profileCompletion,
            'active_opportunities_count' => $activeOpportunities,
            'active_inquiries_count' => $activeInquiriesCount,
            'locked_sessions_count' => $lockedSessions,
            'expired_sessions_count' => $expiredSessions,
            'unread_notifications_count' => $unreadNotifications,
            'posted_requirements_count' => $postedRequirementsCount,
        ];
    }

    private function calculateProfileCompletion(Dealer $dealer): int
    {
        $fields = [
            ($dealer->materials_count ?? $dealer->materials()->count()) > 0,
            ($dealer->locations_count ?? $dealer->locations()->count()) > 0,
            !is_null($dealer->capacity_daily),
            !is_null($dealer->capacity_monthly),
            !is_null($dealer->capacity_unit),
        ];
        // Machines are optional, so they don't count towards completion percentage

        return (int) round((count(array_filter($fields)) / count($fields)) * 100);
    }

    public function postRequirement(array $data, int $userId): Inquiry
    {
        return DB::transaction(function () use ($data, $userId) {
            $dealer = Dealer::where('user_id', $userId)->firstOrFail();

            $visibility = $data['visibility'] ?? 'dealers';

            // Create inquiry
            $inquiry = Inquiry::create([
                'poster_id' => $dealer->id, // Store dealer ID, not user ID
                'poster_type' => 'dealer',
                'inquiry_type' => $data['inquiry_type'],
                'intent' => $data['intent'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'urgency' => $data['urgency'],
                'quantity' => $data['quantity'],
                'quantity_unit' => $data['quantity_unit'],
                'size' => $data['size'] ?? null,
                'price' => $data['price'] ?? null,
                'price_unit' => $data['price_unit'] ?? null,
                'price_negotiable' => $data['price_negotiable'] ?? true,
                'approx_price_note' => $data['approx_price_note'] ?? null,
                'thickness' => $data['thickness'] ?? null,
                'thickness_unit' => $data['thickness_unit'] ?? null,
                'machine_condition' => $data['machine_condition'] ?? null,
                'job_type' => $data['job_type'] ?? null,
                'timeline_days' => $data['timeline_days'] ?? null,
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'specs' => $data['specs'] ?? null,
                'attachment_paths' => $data['attachment_paths'] ?? null,
                'deadline' => isset($data['deadline']) ? $data['deadline'] : null,
                'status' => InquiryStatus::MATCHING,
                'posting_fee_paid' => false,
                'posting_fee_amount' => null,
                'visibility' => $visibility,
            ]);

            // Attach materials if provided (array or single material_id for consistency)
            if (isset($data['material_ids']) && is_array($data['material_ids']) && !empty($data['material_ids'])) {
                $inquiry->materials()->sync($data['material_ids']);
            } elseif (isset($data['material_id'])) {
                $inquiry->materials()->sync([$data['material_id']]);
            }

            // Attach machines if provided
            if (isset($data['machine_ids']) && is_array($data['machine_ids']) && !empty($data['machine_ids'])) {
                $inquiry->machines()->sync($data['machine_ids']);
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

            // Server-authoritative posting fee (config/pricing.php). Deducts once; rolls back the
            // whole post on insufficient balance.
            $quote = $this->chargePostingFee($userId, [
                'role' => 'dealer',
                'inquiry_type' => $data['inquiry_type'] ?? 'material',
                'material_id' => $data['material_id'] ?? null,
                'thickness' => $data['thickness'] ?? null,
                'thickness_unit' => $data['thickness_unit'] ?? null,
                'size' => $data['size'] ?? null,
                'size_unit' => $data['size_unit'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'quantity_unit' => $data['quantity_unit'] ?? null,
                'urgency' => $data['urgency'] ?? 'normal',
            ], $inquiry->id, ['source' => 'dealer_requirement_post']);
            $inquiry->update([
                'posting_fee_paid' => true,
                'posting_fee_amount' => $quote['total'],
            ]);

            // Create matching session (required for sessions to appear)
            // Note: Using 'ACTIVE' as database enum doesn't have 'MATCHING' yet
            // TODO: Update database enum to include all SessionStatus values
            $session = MatchingSession::create([
                'inquiry_id' => $inquiry->id,
                'status' => SessionStatus::ACTIVE,
                'locked_at' => null, // Open until 10 people respond (express interest)
                'expires_at' => now()->addHours(24), // 24 hours expiry
                'discovery_start' => now(),
                'active_session_start' => now(),
                'is_visible_to_dealers' => in_array($visibility, ['dealers', 'all'], true),
                'is_visible_to_brand' => false, // Never visible to brands (dealer-posted)
            ]);

            // Trigger matchmaking via orchestrator (V1 or V2 based on config)
            $result = $this->matchEngineOrchestrator->runMatchmaking($inquiry);
            $this->matchmakingService->notifyMatchedRecipients(
                $inquiry,
                $result['dealer_ids'] ?? [],
                $result['converter_ids'] ?? [],
                $result['machine_dealer_ids'] ?? []
            );

            return $inquiry->load(['materials', 'finishes', 'dealerLocation', 'items', 'session']);
        });
    }

    public function getRequirements(array $filters = [], int $userId): array
    {
        $dealer = Dealer::where('user_id', $userId)->firstOrFail();
        $query = Inquiry::where('poster_id', $dealer->id)
            ->where('poster_type', 'dealer')
            ->with(['materials', 'machines', 'session']);

        // Filter by inquiry_type
        if (isset($filters['inquiry_type']) && !empty($filters['inquiry_type'])) {
            $query->where('inquiry_type', $filters['inquiry_type']);
        }

        // Filter by intent
        if (isset($filters['intent']) && !empty($filters['intent'])) {
            $query->where('intent', $filters['intent']);
        }

        // Filter by status
        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by urgency
        if (isset($filters['urgency']) && !empty($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
        }

        // Filter by material_id
        if (isset($filters['material_id']) && !empty($filters['material_id'])) {
            $query->whereHas('materials', function ($q) use ($filters) {
                $q->where('materials.id', $filters['material_id']);
            });
        }

        // Filter by machine_id
        if (isset($filters['machine_id']) && !empty($filters['machine_id'])) {
            $query->whereHas('machines', function ($q) use ($filters) {
                $q->where('machines.id', $filters['machine_id']);
            });
        }

        // Sort by created_at (newest first) or updated_at
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $filters['per_page'] ?? 15;
        $inquiries = $query->paginate($perPage);

        $requirementsData = $inquiries->map(function ($inquiry) {
            return [
                'id' => $inquiry->id,
                'inquiry_type' => $inquiry->inquiry_type?->value ?? $inquiry->inquiry_type,
                'intent' => $inquiry->intent?->value ?? $inquiry->intent,
                'title' => $inquiry->title,
                'description' => $inquiry->description,
                'status' => $inquiry->status?->value ?? $inquiry->status,
                'urgency' => $inquiry->urgency,
                'quantity' => $inquiry->quantity,
                'quantity_unit' => $inquiry->quantity_unit,
                'size' => $inquiry->size,
                'price' => $inquiry->price,
                'price_unit' => $inquiry->price_unit,
                'price_negotiable' => $inquiry->price_negotiable,
                'thickness' => $inquiry->thickness,
                'thickness_unit' => $inquiry->thickness_unit,
                'machine_condition' => $inquiry->machine_condition,
                'job_type' => $inquiry->job_type,
                'timeline_days' => $inquiry->timeline_days,
                'location' => $inquiry->location,
                'latitude' => $inquiry->latitude,
                'longitude' => $inquiry->longitude,
                'materials' => $inquiry->materials->map(function ($material) {
                    return [
                        'id' => $material->id,
                        'name' => $material->name,
                    ];
                }),
                'machines' => $inquiry->machines->map(function ($machine) {
                    return [
                        'id' => $machine->id,
                        'name' => $machine->name,
                    ];
                }),
                'responses_count' => $inquiry->responses()->count(),
                'session_id' => $inquiry->session?->id,
                'created_at' => $inquiry->created_at->toIso8601String(),
                'updated_at' => $inquiry->updated_at->toIso8601String(),
            ];
        });

        return [
            'requirements' => $requirementsData->toArray(),
            'pagination' => [
                'current_page' => $inquiries->currentPage(),
                'total' => $inquiries->total(),
                'per_page' => $inquiries->perPage(),
                'last_page' => $inquiries->lastPage(),
                'from' => $inquiries->firstItem(),
                'to' => $inquiries->lastItem(),
            ],
        ];
    }
}

