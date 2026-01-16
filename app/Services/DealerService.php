<?php

namespace App\Services;

use App\Enums\DealerStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\InquiryIntent;
use App\Models\Dealer;
use App\Models\DealerLocation;
use App\Models\DealerMaterialDetail;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use Illuminate\Support\Facades\DB;

class DealerService
{
    public function completeProfile(array $data, int $userId): Dealer
    {
        return DB::transaction(function () use ($data, $userId) {
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
                    ]);
                }
            }

            return $dealer->load(['materials', 'machines', 'locations']);
        });
    }

    public function getDashboard(int $userId): array
    {
        $dealer = Dealer::where('user_id', $userId)->first();

        // If dealer doesn't exist, return empty dashboard
        if (!$dealer) {
            return [
                'profile_completion_percentage' => 0,
                'active_opportunities_count' => 0,
                'locked_sessions_count' => 0,
                'expired_sessions_count' => 0,
                'unread_notifications_count' => 0,
                'posted_requirements_count' => 0,
            ];
        }

        $profileCompletion = $this->calculateProfileCompletion($dealer);

        $activeOpportunities = $dealer->acceptances()
            ->whereHas('inquiry', function ($query) {
                $query->where('status', 'SESSION_LOCKED');
            })
            ->count();

        $lockedSessions = MatchingSession::whereHas('inquiry', function ($query) use ($dealer) {
            $query->whereHas('acceptances', function ($q) use ($dealer) {
                $q->where('dealer_id', $dealer->id);
            });
        })
            ->where('status', 'ACTIVE')
            ->count();

        $expiredSessions = MatchingSession::whereHas('inquiry', function ($query) use ($dealer) {
            $query->whereHas('acceptances', function ($q) use ($dealer) {
                $q->where('dealer_id', $dealer->id);
            });
        })
            ->where('status', 'EXPIRED')
            ->count();

        $unreadNotifications = \App\Models\Notification::where('user_id', $userId)
            ->where('read', false)
            ->count();

        // Count dealer's posted requirements
        $postedRequirementsCount = Inquiry::where('poster_id', $userId)
            ->where('poster_type', 'dealer')
            ->count();

        return [
            'profile_completion_percentage' => $profileCompletion,
            'active_opportunities_count' => $activeOpportunities,
            'locked_sessions_count' => $lockedSessions,
            'expired_sessions_count' => $expiredSessions,
            'unread_notifications_count' => $unreadNotifications,
            'posted_requirements_count' => $postedRequirementsCount,
        ];
    }

    private function calculateProfileCompletion(Dealer $dealer): int
    {
        $fields = [
            $dealer->materials()->count() > 0,
            $dealer->locations()->count() > 0,
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

            // Create inquiry
            $inquiry = Inquiry::create([
                'poster_id' => $userId,
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
                'posting_fee_paid' => $data['posting_fee_paid'] ?? false,
                'posting_fee_amount' => $data['posting_fee_amount'] ?? null,
            ]);

            // Attach materials if provided
            if (isset($data['material_ids']) && is_array($data['material_ids']) && !empty($data['material_ids'])) {
                $inquiry->materials()->sync($data['material_ids']);
            }

            // Attach machines if provided
            if (isset($data['machine_ids']) && is_array($data['machine_ids']) && !empty($data['machine_ids'])) {
                $inquiry->machines()->sync($data['machine_ids']);
            }

            return $inquiry->load(['materials', 'machines']);
        });
    }

    public function getRequirements(array $filters = [], int $userId): array
    {
        $query = Inquiry::where('poster_id', $userId)
            ->where('poster_type', 'dealer')
            ->with(['materials', 'machines']);

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

