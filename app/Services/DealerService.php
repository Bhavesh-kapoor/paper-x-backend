<?php

namespace App\Services;

use App\Enums\DealerStatus;
use App\Models\Dealer;
use App\Models\DealerLocation;
use App\Models\DealerMaterialDetail;
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

        return [
            'profile_completion_percentage' => $profileCompletion,
            'active_opportunities_count' => $activeOpportunities,
            'locked_sessions_count' => $lockedSessions,
            'expired_sessions_count' => $expiredSessions,
            'unread_notifications_count' => $unreadNotifications,
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
}

