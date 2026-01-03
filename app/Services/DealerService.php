<?php

namespace App\Services;

use App\Enums\DealerStatus;
use App\Models\Dealer;
use App\Models\DealerLocation;
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

            // Sync materials
            $dealer->materials()->sync($data['materials_dealt_in']);

            // Sync machines
            $dealer->machines()->sync($data['machines_available']);

            // Delete old locations and create new ones
            $dealer->locations()->delete();
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
            $dealer->machines()->count() > 0,
            $dealer->locations()->count() > 0,
            !is_null($dealer->capacity_daily),
            !is_null($dealer->capacity_monthly),
            !is_null($dealer->capacity_unit),
        ];

        return (int) round((count(array_filter($fields)) / count($fields)) * 100);
    }
}

