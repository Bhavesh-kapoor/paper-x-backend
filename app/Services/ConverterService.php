<?php

namespace App\Services;

use App\Enums\ConverterStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\ResponseStatus;
use App\Models\Converter;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use App\Models\Response;
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

        $converterId = $converter->id;
        $activeSessions = MatchingSession::whereHas('inquiry', function ($query) use ($converterId) {
            $query->where('poster_id', $converterId)
                ->where('poster_type', 'converter');
        })->where('status', 'ACTIVE')->count();

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

        return [
            'profile_completion_percentage' => $converter->profile_complete ? 100 : 0,
            'active_sessions_count' => $activeSessions,
            'my_inquiries_count' => $myInquiries,
            'responses_received_count' => $responsesReceived,
            'unread_notifications_count' => $unreadNotifications,
        ];
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
}





