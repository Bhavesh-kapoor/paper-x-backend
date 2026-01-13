<?php

namespace App\Services;

use App\Enums\ConverterStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Models\Converter;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use Illuminate\Support\Facades\DB;

class ConverterService
{
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
            ];
        }

        $activeSessions = MatchingSession::whereHas('inquiry', function ($query) use ($userId) {
            $query->where('poster_id', $userId)
                ->where('poster_type', 'converter');
        })->where('status', 'ACTIVE')->count();

        $myInquiries = Inquiry::where('poster_id', $userId)
            ->where('poster_type', 'converter')
            ->count();

        $responsesReceived = \App\Models\Response::whereHas('inquiry', function ($query) use ($userId) {
            $query->where('poster_id', $userId)
                ->where('poster_type', 'converter');
        })->count();

        $unreadNotifications = \App\Models\Notification::where('user_id', $userId)
            ->where('read_at', null)
            ->count();

        return [
            'profile_completion_percentage' => $converter->profile_complete ? 100 : 0,
            'active_sessions_count' => $activeSessions,
            'my_inquiries_count' => $myInquiries,
            'responses_received_count' => $responsesReceived,
            'unread_notifications_count' => $unreadNotifications,
        ];
    }
}





