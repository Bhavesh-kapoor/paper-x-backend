<?php

namespace App\Services;

use App\Enums\BrandStatus;
use App\Enums\InquiryStatus;
use App\Models\Brand;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use Illuminate\Support\Facades\DB;

class BrandService
{
    public function completeProfile(array $data, int $userId): Brand
    {
        return DB::transaction(function () use ($data, $userId) {
            $brand = Brand::firstOrCreate(
                ['user_id' => $userId],
                ['status' => BrandStatus::PENDING]
            );

            $brand->update([
                'company_name' => $data['company_name'],
                'brand_name' => $data['brand_name'] ?? null,
                'contact_person_name' => $data['contact_person_name'],
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'] ?? null,
                'gst' => $data['gst'] ?? null,
                'city' => $data['city'] ?? null,
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'profile_complete' => true,
                'status' => BrandStatus::ACTIVE,
            ]);

            // Sync brand types
            if (isset($data['brand_type_ids'])) {
                $brand->brandTypes()->sync($data['brand_type_ids']);
            }

            return $brand->load('brandTypes');
        });
    }

    public function getDashboard(int $userId): array
    {
        $brand = Brand::where('user_id', $userId)->first();
        
        if (!$brand) {
            return [
                'profile_completion_percentage' => 0,
                'my_inquiries_count' => 0,
                'active_sessions_count' => 0,
                'unread_messages_count' => 0,
                'unread_notifications_count' => 0,
            ];
        }

        $myInquiries = Inquiry::where('poster_id', $userId)
            ->where('poster_type', 'brand')
            ->count();

        $activeSessions = MatchingSession::whereHas('inquiry', function ($query) use ($userId) {
            $query->where('poster_id', $userId)
                ->where('poster_type', 'brand');
        })->where('status', 'ACTIVE')->count();

        $unreadMessages = \App\Models\ChatMessage::whereHas('session.inquiry', function ($query) use ($userId) {
            $query->where('poster_id', $userId)
                ->where('poster_type', 'brand');
        })->where('sender_id', '!=', $userId)
        ->where('status', '!=', 'READ')
        ->count();

        $unreadNotifications = \App\Models\Notification::where('user_id', $userId)
            ->where('read_at', null)
            ->count();

        return [
            'profile_completion_percentage' => $brand->profile_complete ? 100 : 0,
            'my_inquiries_count' => $myInquiries,
            'active_sessions_count' => $activeSessions,
            'unread_messages_count' => $unreadMessages,
            'unread_notifications_count' => $unreadNotifications,
        ];
    }
}




