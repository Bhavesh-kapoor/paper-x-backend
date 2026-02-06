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
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class BrandService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

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
                'name' => null, // Always null for user brand profiles (name is only for mill brands)
                'contact_person_name' => $data['contact_person_name'],
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'] ?? null,
                'gst' => $data['gst'] ?? null,
                'state' => $data['state'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
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
                'profile_completion_percentage' => 0,
                'my_inquiries_count' => 0,
                'active_sessions_count' => 0,
                'unread_messages_count' => 0,
                'unread_notifications_count' => 0,
            ];
        }

        $brandId = $brand->id;
        $myInquiries = Inquiry::where('poster_id', $brandId)
            ->where('poster_type', 'brand')
            ->count();

        $activeSessions = MatchingSession::whereHas('inquiry', function ($query) use ($brandId) {
            $query->where('poster_id', $brandId)
                ->where('poster_type', 'brand');
        })->where('status', 'ACTIVE')->count();

        $unreadMessages = \App\Models\ChatMessage::whereHas('session.inquiry', function ($query) use ($brandId) {
            $query->where('poster_id', $brandId)
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

    public function postRequirement(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $brand = Brand::where('user_id', $userId)->firstOrFail();

            if (!$brand->profile_complete) {
                throw new \Exception('Brand profile is incomplete. Please complete your brand profile before posting requirements.', 400);
            }
            
            if ($brand->status !== BrandStatus::ACTIVE) {
                throw new \Exception('Brand profile is not active. Current status: ' . $brand->status->value . '. Please contact support if you believe this is an error.', 400);
            }

            // Calculate posting fee (example: 50 credits per requirement)
            $postingFeeAmount = 50; // Can be made configurable

            // Check wallet balance and deduct credits
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0, 'status' => 'ACTIVE']
            );

            if ($wallet->balance < $postingFeeAmount) {
                throw new \Exception('Insufficient wallet balance. You need ' . $postingFeeAmount . ' credits but only have ' . $wallet->balance . ' credits. Please purchase credits first.', 400);
            }

            // Deduct credits using Wallet model method (handles transaction_id generation)
            $transaction = $wallet->deductCredits(
                $postingFeeAmount,
                'Post requirement fee',
                'REQUIREMENT_POSTED',
                null, // reference_id will be set after inquiry is created
                'inquiry',
                []
            );
            
            if (!$transaction) {
                \Log::error('Failed to create wallet transaction', [
                    'user_id' => $userId,
                    'brand_id' => $brand->id,
                    'amount' => $postingFeeAmount,
                    'wallet_balance' => $wallet->balance,
                ]);
                throw new \Exception('Failed to process payment transaction. Please try again or contact support if the issue persists.', 500);
            }

            // Determine urgency based on timeline
            $urgency = 'normal';
            if ($data['timeline'] === 'Urgent 1-2 Days') {
                $urgency = 'urgent';
            }

            // Parse quantity range to get min and max
            $quantityRange = $data['quantity_range'];
            $quantityParts = explode('-', $quantityRange);
            $minQuantity = isset($quantityParts[0]) ? (float) trim($quantityParts[0]) : 0;
            // Handle "50000+" format
            if (strpos($quantityRange, '+') !== false) {
                $maxQuantity = (float) trim(str_replace('+', '', $quantityParts[0]));
            } else {
                $maxQuantity = isset($quantityParts[1]) ? (float) trim($quantityParts[1]) : $minQuantity;
            }

            // Generate title from requirement data
            $titleParts = [];
            $titleParts[] = $data['requirement_type'];
            if ($data['requirement_type'] === 'Packaging' && isset($data['packaging_type'])) {
                $titleParts[] = $data['packaging_type'];
            }
            $titleParts[] = 'Requirement';
            $title = implode(' ', $titleParts);

            // Create inquiry
            $inquiry = Inquiry::create([
                'brand_id' => $brand->id,
                'poster_id' => $brand->id, // Store brand ID, not user ID
                'poster_type' => 'brand',
                'title' => $title,
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
                'special_needs' => null, // Not sent from frontend
                'design_attachments' => null, // Not sent from frontend
                'location' => $data['location'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'posting_fee_paid' => true,
                'posting_fee_amount' => $postingFeeAmount,
            ]);
            
            // Update transaction with inquiry reference
            $transaction->update([
                'reference_id' => $inquiry->id,
            ]);

            // Find 10 best converters using matchmaking engine
            $inquiry->load('brand');
            $matchedConverters = $this->findBestConverters($inquiry, 10);

            // Create matching session
            $session = MatchingSession::create([
                'inquiry_id' => $inquiry->id,
                'status' => SessionStatus::ACTIVE,
                'locked_at' => now(),
                'expires_at' => now()->addHours(24), // 24 hours for brand-converter sessions
                'discovery_start' => now(),
                'active_session_start' => now(),
            ]);

            // Notify matched converters
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





