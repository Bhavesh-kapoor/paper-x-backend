<?php

namespace App\Services;

use App\Enums\BrandStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\SessionStatus;
use App\Models\Brand;
use App\Models\Converter;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use App\Models\Response;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Domain\MatchEngine\MatchEngineOrchestrator;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class BrandService
{
    public function __construct(
        protected MatchEngineOrchestrator $matchEngineOrchestrator,
        protected NotificationService $notificationService
    ) {
    }

    public function completeProfile(array $data, int $userId): Brand
    {
        return DB::transaction(function () use ($data, $userId) {
            // DB column `name` is NOT NULL; use brand_name or company_name for user brand profiles
            $name = !empty($data['brand_name'])
                ? $data['brand_name']
                : ($data['company_name'] ?? 'Brand');

            $brand = Brand::firstOrCreate(
                ['user_id' => $userId],
                [
                    'status' => BrandStatus::PENDING,
                    'name' => $name,
                ]
            );

            $brand->update([
                'company_name' => $data['company_name'],
                'brand_name' => $data['brand_name'] ?? null,
                'name' => $name,
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
            if (isset($data['brand_type_ids']) && is_array($data['brand_type_ids'])) {
                // Filter out null/empty values
                $brandTypeIds = array_filter($data['brand_type_ids'], function($id) {
                    return !is_null($id) && $id !== '';
                });
                
                if (!empty($brandTypeIds)) {
                    $brand->brandTypes()->sync($brandTypeIds);
                } else {
                    // If array is empty, detach all
                    $brand->brandTypes()->detach();
                }
            } else {
                // If no brand types provided, detach all
                $brand->brandTypes()->detach();
            }

            // Reload brand with brandTypes relationship
            $brand->refresh();
            $brand->load('brandTypes');
            
            // Return brand with brandTypes in response
            return $brand;
        });
    }

    public function getDashboard(int $userId): array
    {
        $brand = Brand::where('user_id', $userId)->first();

        if (!$brand) {
            return [
                'activeInquiries' => 0,
                'newProposals' => 0,
                'unreadMessages' => 0,
                'recentInquiries' => [],
            ];
        }

        $brandId = $brand->id;

        $activeStatuses = [
            InquiryStatus::MATCHING,
            InquiryStatus::POSTED,
            InquiryStatus::RESPONSES_RECEIVED,
            InquiryStatus::LOCKED,
            InquiryStatus::CHAT_ACTIVE,
            InquiryStatus::REPUBLISHED,
        ];

        $activeInquiries = Inquiry::where('poster_id', $brandId)
            ->where('poster_type', 'brand')
            ->whereIn('status', $activeStatuses)
            ->count();

        $newProposals = Response::whereHas('inquiry', function ($query) use ($brandId) {
            $query->where('poster_id', $brandId)
                ->where('poster_type', 'brand');
        })->where('status', \App\Enums\ResponseStatus::PENDING)->count();

        $unreadMessages = \App\Models\ChatMessage::whereHas('session.inquiry', function ($query) use ($brandId) {
            $query->where('poster_id', $brandId)
                ->where('poster_type', 'brand');
        })->where('sender_id', '!=', $userId)
          ->where('status', '!=', 'READ')
          ->count();

        $recentInquiries = Inquiry::where('poster_id', $brandId)
            ->where('poster_type', 'brand')
            ->withCount('responses')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function (Inquiry $inquiry) {
                return [
                    'id' => $inquiry->id,
                    'title' => $inquiry->title ?? 'Untitled Inquiry',
                    'quantity' => $inquiry->quantity_range ?? (string) ($inquiry->quantity ?? '0'),
                    'specifications' => trim(
                        ($inquiry->requirement_type ?? '') .
                        ($inquiry->packaging_type ? ' - ' . $inquiry->packaging_type : '')
                    ) ?: 'General',
                    'urgency' => strtoupper($inquiry->urgency ?? 'normal') === 'URGENT' ? 'URGENT' : 'NORMAL',
                    'status' => $this->mapInquiryStatusForDashboard($inquiry->status),
                    'time' => $inquiry->updated_at?->diffForHumans() ?? '',
                    'matchCount' => $inquiry->responses_count ?? 0,
                ];
            })
            ->toArray();

        return [
            'activeInquiries' => $activeInquiries,
            'newProposals' => $newProposals,
            'unreadMessages' => $unreadMessages,
            'recentInquiries' => $recentInquiries,
        ];
    }

    private function mapInquiryStatusForDashboard(InquiryStatus $status): string
    {
        return match ($status) {
            InquiryStatus::MATCHING,
            InquiryStatus::POSTED,
            InquiryStatus::REPUBLISHED => 'MATCHING',

            InquiryStatus::RESPONSES_RECEIVED => 'NEW',

            InquiryStatus::LOCKED,
            InquiryStatus::CHAT_ACTIVE,
            InquiryStatus::SESSION_LOCKED => 'OPEN',

            InquiryStatus::DEAL_SUCCESS,
            InquiryStatus::DEAL_FAILED,
            InquiryStatus::EXPIRED,
            InquiryStatus::DEAL_WON,
            InquiryStatus::DEAL_LOST,
            InquiryStatus::SESSION_EXPIRED,
            InquiryStatus::BRAND_CANCELLED => 'CLOSED',

            default => 'OPEN',
        };
    }

    public function postRequirement(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $brand = Brand::where('user_id', $userId)->firstOrFail();

            if (!$brand->profile_complete || $brand->status !== BrandStatus::ACTIVE) {
                throw new \Exception('Brand profile must be complete and active to post requirements', 400);
            }

            // Calculate posting fee (example: 50 credits per requirement)
            $postingFeeAmount = 50; // Can be made configurable

            // Check wallet balance and deduct credits
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0, 'status' => 'ACTIVE']
            );

            if ($wallet->balance < $postingFeeAmount) {
                throw new \Exception('Insufficient wallet balance. Please purchase credits first.', 400);
            }

            // Deduct credits
            $wallet->decrement('balance', $postingFeeAmount);

            // Create wallet transaction
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'DEBIT',
                'amount' => $postingFeeAmount,
                'description' => 'Post requirement fee',
                'reference_type' => 'inquiry',
                'status' => 'COMPLETED',
            ]);

            // Determine urgency based on timeline
            $urgency = 'normal';
            if ($data['timeline'] === 'Emergency (Urgent)') {
                $urgency = 'urgent';
            }

            // Parse quantity range to get min and max
            $quantityRange = $data['quantity_range'];
            $quantityParts = explode('-', $quantityRange);
            $minQuantity = isset($quantityParts[0]) ? (float) trim($quantityParts[0]) : 0;
            $maxQuantity = isset($quantityParts[1]) ? (float) trim($quantityParts[1]) : $minQuantity;

            // Create inquiry
            $inquiry = Inquiry::create([
                'brand_id' => $brand->id,
                'poster_id' => $brand->id, // Store brand ID, not user ID
                'poster_type' => 'brand',
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => InquiryStatus::MATCHING,
                'urgency' => $urgency,
                'inquiry_type' => InquiryType::JOB, // Brand requirements are job-type inquiries
                'intent' => 'buy', // Brands are buying services
                'requirement_type' => $data['requirement_type'],
                'packaging_type' => $data['packaging_type'] ?? null,
                'quantity' => $maxQuantity, // Use max quantity for matching
                'quantity_unit' => 'pieces',
                'quantity_range' => $data['quantity_range'],
                'timeline' => $data['timeline'],
                'special_needs' => $data['special_needs'] ?? null,
                'design_attachments' => $data['design_attachments'] ?? null,
                'location' => $data['location'] ?? $brand->location ?? $brand->city,
                'latitude' => $data['latitude'] ?? $brand->latitude,
                'longitude' => $data['longitude'] ?? $brand->longitude,
                'posting_fee_paid' => true,
                'posting_fee_amount' => $postingFeeAmount,
            ]);

            // Create matching session first (orchestrator needs it for MatchmakingLog)
            $session = MatchingSession::create([
                'inquiry_id' => $inquiry->id,
                'status' => SessionStatus::ACTIVE,
                'locked_at' => now(),
                'expires_at' => now()->addHours(24), // 24 hours for brand-converter sessions
                'discovery_start' => now(),
                'active_session_start' => now(),
            ]);

            // Run matchmaking via orchestrator (V1 or V2 based on config)
            $inquiry->load(['brand', 'items', 'materials', 'session']);
            $result = $this->matchEngineOrchestrator->runMatchmaking($inquiry);

            // Notify matched converters
            $matchedConverters = $result['converter_ids']
                ? Converter::whereIn('id', $result['converter_ids'])->get()
                : collect();
            foreach ($matchedConverters as $converter) {
                $this->notificationService->create(
                    $converter->user_id,
                    'NEW_OPPORTUNITY',
                    'New Brand Requirement',
                    "A brand has posted a new requirement matching your profile.",
                    $inquiry
                );
            }

            return [
                'inquiry_id' => $inquiry->id,
                'session_id' => $session->id,
                'matched_converters_count' => count($matchedConverters),
                'message' => 'Requirement posted successfully',
            ];
        });
    }

    public function getMyInquiries(int $userId, array $filters = []): array
    {
        $brand = Brand::where('user_id', $userId)->firstOrFail();
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $query = Inquiry::where('poster_id', $brand->id)
            ->where('poster_type', 'brand')
            ->with(['session', 'responses'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
        }

        $inquiries = $query->paginate($perPage, ['*'], 'page', $page);

        $inquiriesData = $inquiries->map(function ($inquiry) {
            return [
                'id' => $inquiry->id,
                'title' => $inquiry->title,
                'description' => $inquiry->description,
                'requirement_type' => $inquiry->requirement_type,
                'packaging_type' => $inquiry->packaging_type,
                'quantity_range' => $inquiry->quantity_range,
                'timeline' => $inquiry->timeline,
                'special_needs' => $inquiry->special_needs,
                'status' => $inquiry->status->value,
                'urgency' => $inquiry->urgency,
                'location' => $inquiry->location,
                'design_attachments' => $inquiry->design_attachments,
                'responses_count' => $inquiry->responses()->count(),
                'has_active_session' => $inquiry->session !== null && $inquiry->session->status === SessionStatus::ACTIVE,
                'session_id' => $inquiry->session?->id,
                'created_at' => $inquiry->created_at->toIso8601String(),
            ];
        });

        return [
            'inquiries' => $inquiriesData->toArray(),
            'pagination' => [
                'current_page' => $inquiries->currentPage(),
                'total' => $inquiries->total(),
                'per_page' => $inquiries->perPage(),
                'last_page' => $inquiries->lastPage(),
            ],
        ];
    }

    private function findBestConverters(Inquiry $inquiry, int $limit = 10): array
    {
        // Get brand to access city
        $brand = $inquiry->brand;
        $brandCity = $brand->city ?? null;

        // Get converters that match the brand's city/location
        $query = Converter::where('status', 'ACTIVE')
            ->where('profile_complete', true)
            ->with(['user', 'converterTypes', 'finishedProducts']);

        // Filter by city if available
        if ($brandCity) {
            $query->where('factory_city', $brandCity);
        }

        // Get all matching converters
        $converters = $query->get();

        // If we don't have enough converters in the same city, expand search
        if ($converters->count() < $limit && $brandCity) {
            $additionalConverters = Converter::where('status', 'ACTIVE')
                ->where('profile_complete', true)
                ->where('factory_city', '!=', $brandCity)
                ->with(['user', 'converterTypes', 'finishedProducts'])
                ->get();
            
            $converters = $converters->merge($additionalConverters);
        }

        // Score and rank converters
        $scoredConverters = $converters->map(function ($converter) use ($inquiry, $brandCity) {
            return [
                'converter' => $converter,
                'score' => $this->calculateConverterMatchScore($inquiry, $converter, $brandCity),
            ];
        })->sortByDesc('score');

        // Return top N converters as array of Converter models
        return $scoredConverters->take($limit)->pluck('converter')->values()->all();
    }

    private function calculateConverterMatchScore(Inquiry $inquiry, Converter $converter, ?string $brandCity = null): int
    {
        $score = 0;

        // City match (40 points)
        if ($brandCity && $converter->factory_city === $brandCity) {
            $score += 40;
        } elseif ($brandCity && $converter->factory_state) {
            // Same state gets partial points (would need to check state match)
            $score += 20;
        }

        // Capacity match (30 points)
        if ($converter->capacity_monthly) {
            $inquiryMaxQuantity = $this->parseQuantityRange($inquiry->quantity_range);
            if ($converter->capacity_monthly >= $inquiryMaxQuantity) {
                $score += 30;
            } elseif ($converter->capacity_monthly >= ($inquiryMaxQuantity * 0.5)) {
                $score += 15;
            }
        }

        // Converter type match (20 points) - based on requirement type
        // This can be enhanced based on converter types and requirement types
        $score += 20;

        // Profile completeness (10 points)
        if ($converter->profile_complete) {
            $score += 10;
        }

        return min(100, $score);
    }

    private function parseQuantityRange(string $range): float
    {
        $parts = explode('-', $range);
        return isset($parts[1]) ? (float) trim($parts[1]) : (float) trim($parts[0]);
    }
}





